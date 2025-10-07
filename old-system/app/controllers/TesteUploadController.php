<?php

namespace app\controllers;

class TesteUploadController extends AbstractController
{
    public function __construct() {}

    public function handleUpload()
    {
        try {
            $files = $this->request->getRequest( 'file' );

            foreach ( $files as $inputName => $fileInfo ) {
                if ( is_array( $fileInfo[ 0 ] ) ) {
                    // Múltiplos arquivos
                    foreach ( $fileInfo as $file ) {
                        $fileName    = $this->request->generateUniqueFileName( $file );
                        $destination = "uploads/{$fileName}";
                        $this->request->moveUploadedFile( $file, $destination );
                    }
                } else {
                    // Arquivo único
                    $fileName    = $this->request->generateUniqueFileName( $fileInfo );
                    $destination = "uploads/{$fileName}";
                    $this->request->moveUploadedFile( $fileInfo, $destination );
                }
            }

            return [ 'success' => true, 'message' => 'Arquivos enviados com sucesso' ];

        } catch ( RuntimeException $e ) {
            return [ 'success' => false, 'message' => $e->getMessage() ];
        }
    }

    // No seu controller
    public function upload()
    {
        $request = Request::create();
        $files   = $request->all();

        // Para um único arquivo
        if ( $request->hasFile( 'document' ) ) {
            $file = $files[ 'document' ];

            if ( $file[ 'is_valid' ] ) {
                $destination = "uploads/" . $file[ 'name' ];
                if ( $request->moveUploadedFile( $file, $destination ) ) {
                    return [ 
                        'success' => true,
                        'message' => 'Arquivo enviado com sucesso',
                        'path'    => $destination
                    ];
                }
            }

            return [ 
                'success' => false,
                'message' => $file[ 'error_message' ]
            ];
        }

        // Para múltiplos arquivos
        if ( $request->hasFile( 'documents' ) ) {
            $uploadedFiles = [];
            $errors        = [];

            foreach ( $files[ 'documents' ] as $file ) {
                if ( $file[ 'is_valid' ] ) {
                    $destination = "uploads/" . $file[ 'name' ];
                    if ( $request->moveUploadedFile( $file, $destination ) ) {
                        $uploadedFiles[] = $destination;
                    } else {
                        $errors[] = "Erro ao mover o arquivo {$file[ 'name' ]}";
                    }
                } else {
                    $errors[] = $file[ 'error_message' ];
                }
            }

            return [ 
                'success'        => empty( $errors ),
                'uploaded_files' => $uploadedFiles,
                'errors'         => $errors
            ];
        }
    }

}