<?php

return [

    'servers' => [

        'memory'              => [
            'command' => 'npx',
            'args'    => [ '-y', '@modelcontextprotocol/server-memory' ],
            'env'     => [
                'MEMORY_FILE_PATH' => base_path( 'memory.json' ),
            ],
        ],

        'context7'            => [
            'command' => 'npx',
            'args'    => [ '-y', '@upstash/context7-mcp@latest' ],
            'env'     => [
                'DEFAULT_MINIMUM_TOKENS' => '10000',
            ],
        ],

        'sequential-thinking' => [
            'command' => 'uvx',
            'args'    => [
                '--from',
                'git+https://github.com/arben-adm/mcp-sequential-thinking',
                '--with',
                'portalocker',
                'mcp-sequential-thinking',
            ],
        ],

        'phpocalypse'         => [
            'command' => 'npx',
            'args'    => [
                'tsx',
                base_path( 'tools/PHPocalypse-MCP/src/index.ts' ),
                '--config',
                base_path( 'phpocalypse-mcp.yaml' ),
            ],
        ],

        'filesystem'          => [
            'command' => 'npx',
            'args'    => [ '-y', '@modelcontextprotocol/server-filesystem' ],
            'env'     => [
                'ALLOWED_DIRECTORY' => base_path(),
            ],
        ],

        'testsprite'          => [
            'command' => 'npx',
            'args'    => [ '@testsprite/testsprite-mcp@latest' ],
            'env'     => [
                'API_KEY' => env( 'TESTSPRITE_API_KEY' ),
            ],
        ],

    ],

];
