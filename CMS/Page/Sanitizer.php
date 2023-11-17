<?php
namespace Checlou\FlatFileCMSBundle\CMS\Page;

class Sanitizer
{

    public static function sanitize($words): string {

        $sanitized = mb_strtolower($words, 'UTF-8');
        $sanitized = preg_replace('/\&/', 'et', $sanitized);

        // Les caractères de non sens
        $sanitized = preg_replace('/(\'|\[|\]|{|}|\(|\)|\\|\^|~|#|`|£|=|\$|\*|§|¤|\!|\?)/', '', $sanitized);

        // les caractères de ponctuation
        $sanitized = preg_replace('/(,|;|\.|:|>|\||\+|\/)/', '-', $sanitized);

        // Les caractères accentués
        $sanitized = preg_replace('/(é|è|ê|€|ë)/', 'e', $sanitized);
        $sanitized = preg_replace('/(à|â|ä|ã|@)/', 'a', $sanitized);
        $sanitized = preg_replace('/(ì|ï|î)/', 'i', $sanitized);
        $sanitized = preg_replace('/(ò|ö|ô)/', 'o', $sanitized);
        $sanitized = preg_replace('/(ù|ü|û|µ)/', 'u', $sanitized);
        $sanitized = preg_replace('/(ç)/', 'c', $sanitized);

        // Les espaces
        $sanitized = trim($sanitized);
        $sanitized = preg_replace('/\s{1,n}/', '-', $sanitized);

        // On fait une dernière purge en encodant et supprimmant les caractères encodés
        $sanitized = rawurlencode($sanitized);
        $sanitized = preg_replace('/%([A-Z|0-9])([A-Z|0-9])/', '-', $sanitized);
        $sanitized = preg_replace('/_{2}/', '_', $sanitized);

        return $sanitized;
    }

    /**
     * Replace - by space and turn each word to a firt letter uppercase.
     *
     * @param $slug
     *
     * @return string
     */
    public static function unsanitize($slug): string {
        return ucwords(str_replace('-', ' ', $slug));
    }

}