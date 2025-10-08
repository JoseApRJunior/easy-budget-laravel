<?php

namespace app\controllers;

use core\library\Response;
use Endroid\QrCode\QrCode;
use Zxing\QrReader;

class QrCodeController extends AbstractController
{
    /**
     * Generate and read QR code
     *
     * @param string $text Text or URL to encode
     * @return Response
     */
    public function handle( string $text ): Response
    {
        try {
            // Create QR Code
            $qrCode = new QrCode( $text );

            // Save to temp file
            $tempFile = tempnam( sys_get_temp_dir(), 'qr_' );
            $qrCode->writeFile( $tempFile );

            // Read QR code
            $qrcodeReader = new QrReader( $tempFile );
            $decodedText  = $qrcodeReader->text();

            // Clean up temp file
            unlink( $tempFile );

            return new Response( [ 
                'success' => true,
                'message' => 'QR Code processed successfully',
                'data'    => [ 
                    'original_text' => $text,
                    'decoded_text'  => $decodedText
                ]
            ] );

        } catch ( \Throwable $e ) {
            return new Response( [ 
                'success' => false,
                'message' => 'Error processing QR code',
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Generate QR code only
     *
     * @param string $text Text or URL to encode
     * @return Response
     */
    public function generate( string $text ): Response
    {
        try {
            $qrCode = new QrCode( $text );

            return Response::json( [ 
                'success' => true,
                'message' => 'QR Code generated successfully',
                'data'    => [ 
                    'qr_code' => base64_encode( $qrCode->writeString() )
                ]
            ] );

        } catch ( \Throwable $e ) {
            return Response::json( [ 
                'success' => false,
                'message' => 'Error generating QR code',
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

}
