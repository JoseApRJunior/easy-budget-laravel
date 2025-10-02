<?php

use app\database\entitiesORM\ResourceEntity;
use core\library\Session;
use core\library\Twig;
use Doctrine\DBAL\Exception as DBALException;

/** @var Twig $this */
return [
    'getResource'    => function (string $slug = null) {
        if ( $slug !== null ) {
            // Verificar se já temos o recurso em sessão
            // $sessionKey = 'resource_' . $slug;
            // if ( !Session::get( $sessionKey ) ) {
            //     try {
            //         $resource = ( new Resource(
            //             $this->getConnection(),
            //         ) )->findBySlug( $slug );

            //         Session::set( $sessionKey, $resource );
            //     } catch ( DBALException | \Exception $e ) {
            //         // Em caso de erro, criar um recurso padrão
            //         $defaultResource = new ResourceEntity( $this->getConnection() );
            //         $defaultResource->setStatus( 'active' );
            //         Session::set( $sessionKey, $defaultResource );
            //     }
            // }

            // return Session::get( $sessionKey );
        }

        // // Retornar um recurso padrão se não houver slug
        // $defaultResource = new Resource( $this->getConnection() );
        // $defaultResource->setStatus( 'active' );
        // return $defaultResource;
    },
    'title'          => function () {
        return Session::get( 'title' );
    },
    'script'         => function () {
        return Session::get( 'script' );
    },
    'entityProperty' => function ($entity, $property) {
        if ( is_object( $entity ) && method_exists( $entity, $property ) ) {
            return $entity->$property();
        } elseif ( is_object( $entity ) && method_exists( $entity, 'get' . ucfirst( $property ) ) ) {
            $method = 'get' . ucfirst( $property );
            return $entity->$method();
        }
        return null;
    },

];
