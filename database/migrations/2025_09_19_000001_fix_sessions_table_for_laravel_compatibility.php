<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Esta migração pode invalidar sessões ativas durante deploy se a tabela for recriada.
     * Use em ambiente de teste primeiro. Alternativa: alter-in-place para preservar dados.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        Log::info( "Using {$driver} compatible path for sessions migration." );

        $hasDbal = class_exists( \Doctrine\DBAL\DriverManager::class);

        $forceInPlace = env( 'SESSIONS_ALTER_IN_PLACE', false ) && $driver !== 'sqlite';

        $chunkSize = (int) env( 'SESSIONS_CHUNK_SIZE', 1000 );

        if ( Schema::hasTable( 'sessions' ) ) {
            // Verificar colunas essenciais para compatibilidade Laravel
            $hasPayload      = Schema::hasColumn( 'sessions', 'payload' );
            $hasLastActivity = Schema::hasColumn( 'sessions', 'last_activity' );

            // Portable type detection using Laravel Schema
            $idType = null;
            if ( $hasDbal && Schema::hasColumn( 'sessions', 'id' ) ) {
                try {
                    $idType = Schema::getColumnType( 'sessions', 'id' );
                } catch ( \Exception $e ) {
                    Log::warning( 'DBAL exception on getColumnType for id: ' . $e->getMessage() );
                    $idType = null;
                }
            }

            $lastActivityType = null;
            if ( $hasDbal && Schema::hasColumn( 'sessions', 'last_activity' ) ) {
                try {
                    $lastActivityType = Schema::getColumnType( 'sessions', 'last_activity' );
                } catch ( \Exception $e ) {
                    Log::warning( 'DBAL exception on getColumnType for last_activity: ' . $e->getMessage() );
                    $lastActivityType = null;
                }
            }

            $idIsString        = $idType && in_array( strtolower( $idType ), [ 'string', 'varchar', 'char', 'text' ] );
            $lastActivityIsInt = $lastActivityType && in_array( strtolower( $lastActivityType ), [ 'integer', 'int', 'bigint', 'smallint', 'tinyint', 'mediumint' ] );

            $needsRebuild = !$hasPayload || !$hasLastActivity || !$idIsString || !$lastActivityIsInt;

            $dropped = false;

            if ( $needsRebuild && !$forceInPlace ) {
                // Enhance needsRebuild detection for legacy columns indicating rebuild is needed
                $hasSessionToken      = Schema::hasColumn( 'sessions', 'session_token' );
                $hasSessionData       = Schema::hasColumn( 'sessions', 'session_data' );
                $enhancedNeedsRebuild = $needsRebuild || $hasSessionToken || $hasSessionData;

                if ( !$enhancedNeedsRebuild ) {
                    Log::info( 'Schema de sessões já compatível com Laravel; pulando rebuild.' );
                    return;
                }

                Log::warning( 'Executando rebuild de sessões devido a incompatibilidades legacy (incluindo session_token/session_data). Ative SESSIONS_ALTER_IN_PLACE=true para in-place se preferir.' );

                // Rebuild path: Create sessions_new with Laravel schema, backfill original, chunked insert with mapping, atomic rename
                $totalProcessed = 0;
                $totalInserted  = 0;
                try {
                    // Step 1: Create sessions_new with standard Laravel sessions schema
                    Schema::create( 'sessions_new', function (Blueprint $table) {
                        $table->string( 'id' )->primary();
                        $table->unsignedBigInteger( 'user_id' )->nullable()->index( 'sessions_user_id_index' );
                        $table->string( 'ip_address', 45 )->nullable();
                        $table->text( 'user_agent' )->nullable();
                        $table->longText( 'payload' );
                        $table->integer( 'last_activity' )->index();
                    } );

                    // Step 2: Backfill missing columns in original sessions table temporarily
                    // Add id if only session_token exists (map session_token to id)
                    if ( $hasSessionToken && !Schema::hasColumn( 'sessions', 'id' ) ) {
                        Schema::table( 'sessions', function (Blueprint $table) {
                            $table->string( 'id', 255 )->nullable()->after( 'session_token' );
                        } );
                        // Populate id from session_token
                        DB::statement( "UPDATE sessions SET id = session_token WHERE session_token IS NOT NULL" );
                        Log::info( 'Backfilled id from session_token in original table.' );
                    }

                    // Add payload if missing, convert from session_data if present (Comment 2)
                    if ( !Schema::hasColumn( 'sessions', 'payload' ) ) {
                        Schema::table( 'sessions', function (Blueprint $table) {
                            $table->longText( 'payload' )->nullable();
                        } );
                    }
                    if ( $hasSessionData ) {
                        if ( $driver === 'mysql' ) {
                            DB::table( 'sessions' )->orderBy( 'id' )->chunk( $chunkSize, function ($rows) use ($hasSessionToken, &$totalProcessed) {
                                $totalProcessed += count( $rows );
                                foreach ( $rows as $row ) {
                                    if ( !empty( $row->payload ) ) continue; // Skip if already populated
                                    $arr        = json_decode( $row->session_data, true ) ?: [];
                                    $payload    = base64_encode( serialize( $arr ) );
                                    $identifier = $row->session_token ?? $row->id;
                                    if ( $identifier ) {
                                        DB::table( 'sessions' )->where( $hasSessionToken ? 'session_token' : 'id', $identifier )->update( [ 'payload' => $payload ] );
                                    }
                                }
                            } );
                        } else {
                            DB::table( 'sessions' )->chunk( $chunkSize, function ($rows) use ($hasSessionToken, &$totalProcessed) {
                                $totalProcessed += count( $rows );
                                foreach ( $rows as $row ) {
                                    if ( !empty( $row->payload ) ) continue; // Skip if already populated
                                    $arr        = json_decode( $row->session_data, true ) ?: [];
                                    $payload    = base64_encode( serialize( $arr ) );
                                    $identifier = $row->session_token ?? $row->id;
                                    if ( $identifier ) {
                                        DB::table( 'sessions' )->where( $hasSessionToken ? 'session_token' : 'id', $identifier )->update( [ 'payload' => $payload ] );
                                    }
                                }
                            } );
                        }
                        Log::info( 'Backfilled payload from session_data in original table (rebuild).' );
                    } else if ( Schema::hasColumn( 'sessions', 'payload' ) ) {
                        // Set empty payload where null
                        DB::table( 'sessions' )->whereNull( 'payload' )->update( [ 'payload' => base64_encode( serialize( [] ) ) ] );
                    }

                    // Add last_activity if missing
                    if ( !Schema::hasColumn( 'sessions', 'last_activity' ) ) {
                        Schema::table( 'sessions', function (Blueprint $table) {
                            $table->integer( 'last_activity' )->nullable();
                        } );
                        DB::table( 'sessions' )->update( [ 'last_activity' => time() ] ); // Backfill with current timestamp
                        Log::info( 'Backfilled last_activity in original table.' );
                    }

                    // Step 3: Determine id source (session_token if present, else id)
                    $idource = $hasSessionToken ? 'session_token' : 'id';

                    // Step 4: Chunked insertion into sessions_new, mapping legacy columns, skip if already processed (check if payload exists and non-empty)
                    DB::table( 'sessions' )->whereNotNull( 'payload' )->chunk( $chunkSize, function ($rows) use ($idource, $hasSessionData, &$totalInserted) {
                        $insertData = [];
                        foreach ( $rows as $row ) {
                            // Skip if already processed (e.g., payload is empty or legacy indicator)
                            if ( empty( $row->payload ) && $hasSessionData ) continue; // Assume unprocessed if session_data but no payload

                            $insertData[] = [ 
                                'id'            => (string) ( $row->{$idource} ?? \Illuminate\Support\Str::uuid() ),
                                'user_id'       => $row->user_id ?? null,
                                'ip_address'    => $row->ip_address ?? null,
                                'user_agent'    => $row->user_agent ?? null,
                                'payload'       => $row->payload ?? base64_encode( serialize( [] ) ),
                                'last_activity' => $row->last_activity ?? time(),
                            ];
                        }
                        if ( !empty( $insertData ) ) {
                            DB::table( 'sessions_new' )->insert( $insertData );
                            $totalInserted += count( $insertData );
                        }
                    } );

                    Log::info( "Inserted {$totalInserted} rows into sessions_new." );

                    // Step 5: Atomic rename, driver-specific
                    if ( $driver === 'mysql' ) {
                        DB::statement( 'RENAME TABLE sessions TO sessions_old, sessions_new TO sessions' );
                        // Step 6: Drop old table on success
                        Schema::dropIfExists( 'sessions_old' );
                    } else {
                        // Portable: Drop original, rename new to sessions
                        Schema::dropIfExists( 'sessions' );
                        DB::statement( 'ALTER TABLE sessions_new RENAME TO sessions' );
                    }
                    Log::info( 'Rebuild successful: sessions table rebuilt and renamed (' . $driver . ').' );

                    // Clean up temporary columns in new table if added (but since renamed, they aren't there; original backfill was temp)
                    // Note: Temporary columns were added to original (now dropped), so no cleanup needed

                    if ( app()->environment( [ 'local', 'testing' ] ) ) {
                        Log::info( "Migration summary: {$totalProcessed} rows processed, {$totalInserted} rows inserted.", [ 'totalProcessed' => $totalProcessed, 'totalInserted' => $totalInserted ] );
                    }

                } catch ( \Exception $e ) {
                    Log::error( 'Falha no rebuild de sessões: ' . $e->getMessage() . '. Rollback manual.' );
                    // Rollback: Drop sessions_new if exists, preserve/restore original sessions
                    Schema::dropIfExists( 'sessions_new' );
                    if ( $driver === 'mysql' ) {
                        if ( Schema::hasTable( 'sessions_old' ) ) {
                            // If rename partially succeeded (unlikely), rename back
                            DB::statement( 'RENAME TABLE sessions TO sessions_new, sessions_old TO sessions' );
                            Schema::dropIfExists( 'sessions_new' );
                        }
                    } else {
                        // For non-MySQL, if drop succeeded but rename failed, sessions is gone - log and re-throw
                        if ( !Schema::hasTable( 'sessions' ) ) {
                            Log::error( 'Original sessions table lost during rebuild rollback (non-MySQL). Manual restoration required.' );
                        }
                    }
                    // Re-throw if critical
                    throw $e;
                }
            } else {
                // Altera in-place para preservar sessões existentes, adicionando colunas ausentes.
                // Evitamos alteração da PK in-place porque detecção de tipo não é confiável
                // e há risco de perda de dados em sessões ativas.
                $totalProcessed  = 0;
                $totalInserted   = 0;
                $hasUserId       = Schema::hasColumn( 'sessions', 'user_id' );
                $hasIpAddress    = Schema::hasColumn( 'sessions', 'ip_address' );
                $hasUserAgent    = Schema::hasColumn( 'sessions', 'user_agent' );
                $hasSessionData  = Schema::hasColumn( 'sessions', 'session_data' );
                $hasSessionToken = Schema::hasColumn( 'sessions', 'session_token' );
                $hasExpiresAt    = Schema::hasColumn( 'sessions', 'expires_at' );
                $hasIsActive     = Schema::hasColumn( 'sessions', 'is_active' );

                // Ensure id is string primary key, map session_token to id if needed (Comment 3)
                $idType = null;
                if ( $hasDbal && Schema::hasColumn( 'sessions', 'id' ) ) {
                    try {
                        $idType = Schema::getColumnType( 'sessions', 'id' );
                    } catch ( \Exception $e ) {
                        Log::warning( 'DBAL exception on getColumnType for id (in-place): ' . $e->getMessage() );
                        $idType = null;
                    }
                }
                $idIsString = $idType && in_array( strtolower( $idType ), [ 'string', 'varchar', 'char', 'text' ] );
                if ( !$idIsString && Schema::hasColumn( 'sessions', 'id' ) ) {
                    if ( $driver === 'mysql' ) {
                        DB::statement( 'ALTER TABLE sessions DROP PRIMARY KEY' );
                        DB::statement( 'ALTER TABLE sessions MODIFY COLUMN id VARCHAR(255) NOT NULL' );
                        DB::statement( 'ALTER TABLE sessions ADD PRIMARY KEY (id)' );
                    } else {
                        if ( $driver === 'pgsql' ) {
                            DB::statement( 'ALTER TABLE sessions DROP CONSTRAINT IF EXISTS sessions_pkey;' );
                            DB::statement( 'ALTER TABLE sessions ALTER COLUMN id TYPE VARCHAR(255) USING id::VARCHAR(255);' );
                            DB::statement( 'ALTER TABLE sessions ADD PRIMARY KEY (id);' );
                        } elseif ( $driver === 'sqlite' ) {
                            Log::warning( 'For SQLite, id type change requires table rebuild. Skipping automatic change.' );
                        } else {
                            Log::warning( "Driver {$driver} not supported for automatic id type change. Manual intervention needed." );
                        }
                    }
                    Log::info( 'Adjusted id column to string primary key in-place.' );
                }
                if ( $hasSessionToken ) {
                    DB::statement( "UPDATE sessions SET id = session_token WHERE session_token IS NOT NULL AND (id IS NULL OR id <> session_token)" );
                    Log::info( 'Mapped session_token to id in-place.' );
                }

                // Add and backfill core columns before any drops (Comments 1 & 4, for all drivers)
                if ( !$hasPayload ) {
                    Schema::table( 'sessions', function (Blueprint $table) {
                        $table->longText( 'payload' )->nullable();
                    } );
                }
                // Chunked backfill payload from session_data (Comment 2)
                if ( $hasSessionData ) {
                    if ( $driver === 'mysql' ) {
                        DB::table( 'sessions' )->orderBy( 'id' )->chunk( $chunkSize, function ($rows) use ($hasSessionToken, &$totalProcessed, &$totalInserted) {
                            $totalProcessed += count( $rows );
                            foreach ( $rows as $row ) {
                                if ( !empty( $row->payload ) ) continue; // Skip if already populated
                                $arr        = json_decode( $row->session_data, true ) ?: [];
                                $payload    = base64_encode( serialize( $arr ) );
                                $identifier = $row->session_token ?? $row->id;
                                if ( $identifier ) {
                                    $updated = DB::table( 'sessions' )->where( $hasSessionToken ? 'session_token' : 'id', $identifier )->update( [ 'payload' => $payload ] );
                                    if ( $updated > 0 ) {
                                        $totalInserted++;
                                    }
                                }
                            }
                        } );
                    } else {
                        DB::table( 'sessions' )->chunk( $chunkSize, function ($rows) use ($hasSessionToken, &$totalProcessed, &$totalInserted) {
                            $totalProcessed += count( $rows );
                            foreach ( $rows as $row ) {
                                if ( !empty( $row->payload ) ) continue; // Skip if already populated
                                $arr        = json_decode( $row->session_data, true ) ?: [];
                                $payload    = base64_encode( serialize( $arr ) );
                                $identifier = $row->session_token ?? $row->id;
                                if ( $identifier ) {
                                    $updated = DB::table( 'sessions' )->where( $hasSessionToken ? 'session_token' : 'id', $identifier )->update( [ 'payload' => $payload ] );
                                    if ( $updated > 0 ) {
                                        $totalInserted++;
                                    }
                                }
                            }
                        } );
                    }
                    Log::info( 'Backfilled payload from session_data in-place (' . $driver . ').' );
                } else if ( Schema::hasColumn( 'sessions', 'payload' ) ) {
                    // Set empty payload where null
                    DB::table( 'sessions' )->whereNull( 'payload' )->update( [ 'payload' => base64_encode( serialize( [] ) ) ] );
                }

                if ( !$hasLastActivity ) {
                    Schema::table( 'sessions', function (Blueprint $table) {
                        $table->integer( 'last_activity' )->nullable()->index();
                    } );
                }
                // Backfill last_activity where null (Comment 1)
                if ( Schema::hasColumn( 'sessions', 'last_activity' ) ) {
                    DB::table( 'sessions' )->whereNull( 'last_activity' )->update( [ 'last_activity' => time() ] );
                    Log::info( 'Backfilled last_activity where null in-place.' );
                }

                // Now drop legacy columns after backfills (Comments 1 & 4)
                if ( $hasSessionData ) {
                    Schema::table( 'sessions', function (Blueprint $table) {
                        $table->dropColumn( 'session_data' );
                    } );
                }
                if ( $hasExpiresAt ) {
                    Schema::table( 'sessions', function (Blueprint $table) {
                        $table->dropColumn( 'expires_at' );
                    } );
                }
                if ( $hasIsActive ) {
                    Schema::table( 'sessions', function (Blueprint $table) {
                        $table->dropColumn( 'is_active' );
                    } );
                }
                if ( $hasSessionToken ) {
                    Schema::table( 'sessions', function (Blueprint $table) {
                        $table->dropColumn( 'session_token' );
                    } );
                }

                // Add/adjust other columns
                Schema::table( 'sessions', function (Blueprint $table) use ($hasUserId, $hasIpAddress, $hasUserAgent) {
                    if ( !$hasUserId ) {
                        $table->unsignedBigInteger( 'user_id' )->nullable()->index( 'sessions_user_id_index' );
                    } else {
                        $table->index( 'user_id', 'sessions_user_id_index' );
                    }

                    if ( !$hasIpAddress ) {
                        $table->string( 'ip_address', 45 )->nullable();
                    }
                    if ( !$hasUserAgent ) {
                        $table->text( 'user_agent' )->nullable();
                    }
                    // Ensure last_activity index if exists
                    if ( Schema::hasColumn( 'sessions', 'last_activity' ) ) {
                        $table->index( 'last_activity' );
                    }
                } );

                // Final type adjustments if needed (after drops, Comment 4)
                $lastActivityType = null;
                if ( $hasDbal && Schema::hasColumn( 'sessions', 'last_activity' ) ) {
                    try {
                        $lastActivityType = Schema::getColumnType( 'sessions', 'last_activity' );
                    } catch ( \Exception $e ) {
                        Log::warning( 'DBAL exception on getColumnType for last_activity (final): ' . $e->getMessage() );
                        $lastActivityType = null;
                    }
                }
                $lastActivityIsInt = $lastActivityType && in_array( strtolower( $lastActivityType ), [ 'integer', 'int', 'bigint', 'smallint', 'tinyint', 'mediumint' ] );
                if ( !$lastActivityIsInt && Schema::hasColumn( 'sessions', 'last_activity' ) ) {
                    if ( $driver === 'mysql' ) {
                        DB::statement( 'ALTER TABLE sessions MODIFY COLUMN last_activity INT NOT NULL' );
                    } else {
                        if ( $driver === 'pgsql' ) {
                            DB::statement( 'ALTER TABLE sessions ALTER COLUMN last_activity TYPE INTEGER USING last_activity::INTEGER;' );
                        } elseif ( $driver === 'sqlite' ) {
                            Log::warning( 'For SQLite, last_activity type change requires table rebuild. Skipping.' );
                        } else {
                            Log::warning( "Driver {$driver} not supported for last_activity type change." );
                        }
                    }
                    Log::info( 'Adjusted last_activity to int in-place.' );

                    if ( app()->environment( [ 'local', 'testing' ] ) ) {
                        Log::info( "Migration summary: {$totalProcessed} rows processed, {$totalInserted} rows inserted.", [ 'totalProcessed' => $totalProcessed, 'totalInserted' => $totalInserted ] );
                    }
                }
            }
        } else {
            // Tabela não existe: criar nova
            Schema::create( 'sessions', function (Blueprint $table) {
                $table->string( 'id' )->primary();
                $table->unsignedBigInteger( 'user_id' )->nullable()->index( 'sessions_user_id_index' );
                $table->string( 'ip_address', 45 )->nullable();
                $table->text( 'user_agent' )->nullable();
                $table->longText( 'payload' );
                $table->integer( 'last_activity' )->index();
            } );
        }

        $totalProcessed = Schema::hasTable( 'sessions' ) ? DB::table( 'sessions' )->count() : 0;
        if ( app()->environment( [ 'local', 'testing' ] ) ) {
            Log::info( "Total de rows processadas na migração de sessões: {$totalProcessed}" );
        }

    }

    /**
     * Reverse the migrations.
     *
     * @throws \LogicException Esta migração é irreversível: recriar o schema legacy
     *                         exigiria conhecimento da estrutura original exata,
     *                         que pode variar. Em rollback, todas as sessões serão
     *                         invalidadas. Use apenas em desenvolvimento após backup.
     *                         Para produção, implemente migração reversa customizada.
     */
    public function down(): void
    {
        // Rollback mínimo: drop e recria com schema Laravel atual (não restaura legacy; sessões serão invalidadas)
        // Nota: Restauração de schema legacy não suportada; backup manual recomendado antes de up().
        if ( Schema::hasTable( 'sessions' ) ) {
            Schema::drop( 'sessions' );
        }
        Schema::create( 'sessions', function (Blueprint $table) {
            $table->string( 'id' )->primary();
            $table->unsignedBigInteger( 'user_id' )->nullable()->index();
            $table->string( 'ip_address', 45 )->nullable();
            $table->text( 'user_agent' )->nullable();
            $table->longText( 'payload' );
            $table->integer( 'last_activity' )->index();
        } );
        Log::warning( 'Rollback de migração de sessões executado: schema Laravel recriado, mas dados legacy não restaurados.' );
    }

};
