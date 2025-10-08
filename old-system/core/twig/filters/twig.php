<?php

if (!file_exists($appFilters = APP_PATH . '/twig/filters/twig.php')) {
    throw new Exception("Please create filters inside app/twig/filters/twig.php file. It should return an array of Twig filters.");
}

$coreFilters = [
    'month_year_pt' => function ($date) {
        $meses = [
            'Jan' => 'Jan',
            'Feb' => 'Fev',
            'Mar' => 'Mar',
            'Apr' => 'Abr',
            'May' => 'Mai',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Ago',
            'Sep' => 'Set',
            'Oct' => 'Out',
            'Nov' => 'Nov',
            'Dec' => 'Dez',
        ];

        $mes = date('M', strtotime($date));
        $ano = date('Y', strtotime($date));

        return $meses[ $mes ] . '/' . $ano;
    },
    'day_month_year_pt' => fn ($date) => date('d/m/Y', strtotime($date)),
    'time_diff' => function ($datetime) {
        $now = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);

        if ($diff->d == 0) {
            if ($diff->h == 0) {
                if ($diff->i == 0) {
                    return "agora mesmo";
                }

                return $diff->i . " min atrás";
            }

            return $diff->h . "h atrás";
        }
        if ($diff->d == 1) {
            return "ontem";
        }
        if ($diff->d < 7) {
            return $diff->d . " dias atrás";
        }

        return $past->format('d/m/Y');
    },
    'format_date_or_default' => function ($date, $format = 'd/m/Y', $default = 'Não informado') {
        if (empty($date)) {
            return $default;
        }

        try {
            $dateTime = ($date instanceof \DateTime) ? $date : new \DateTime($date);

            return $dateTime->format($format);
        } catch (\Exception $e) {
            return $default;
        }
    },
];

$includeAppFilters = require $appFilters;

if (!is_array($includeAppFilters)) {
    throw new Exception("Twig file must return an array");
}

return [ ...$includeAppFilters, ...$coreFilters ];
