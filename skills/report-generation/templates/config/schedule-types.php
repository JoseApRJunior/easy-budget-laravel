<?php

// Configuração de tipos de agendamento de relatórios
return [
    // Agendamento Diário
    'daily' => [
        'name' => 'Diariamente',
        'description' => 'Executa o relatório todos os dias',
        'icon' => 'calendar-day',
        'config_fields' => [
            'hour' => [
                'type' => 'select',
                'label' => 'Hora de Execução',
                'options' => range(0, 23),
                'default' => 9,
                'required' => true,
            ],
            'minute' => [
                'type' => 'select',
                'label' => 'Minuto de Execução',
                'options' => [0, 15, 30, 45],
                'default' => 0,
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_day' => 3,
            'min_interval_hours' => 1,
        ],
        'examples' => [
            'morning' => ['hour' => 9, 'minute' => 0, 'description' => 'Manhã (09:00)'],
            'afternoon' => ['hour' => 14, 'minute' => 0, 'description' => 'Tarde (14:00)'],
            'evening' => ['hour' => 18, 'minute' => 30, 'description' => 'Final do dia (18:30)'],
        ],
    ],

    // Agendamento Semanal
    'weekly' => [
        'name' => 'Semanalmente',
        'description' => 'Executa o relatório uma vez por semana',
        'icon' => 'calendar-week',
        'config_fields' => [
            'day_of_week' => [
                'type' => 'select',
                'label' => 'Dia da Semana',
                'options' => [
                    'monday' => 'Segunda-feira',
                    'tuesday' => 'Terça-feira',
                    'wednesday' => 'Quarta-feira',
                    'thursday' => 'Quinta-feira',
                    'friday' => 'Sexta-feira',
                    'saturday' => 'Sábado',
                    'sunday' => 'Domingo',
                ],
                'default' => 'monday',
                'required' => true,
            ],
            'hour' => [
                'type' => 'select',
                'label' => 'Hora de Execução',
                'options' => range(8, 18),
                'default' => 10,
                'required' => true,
            ],
            'minute' => [
                'type' => 'select',
                'label' => 'Minuto de Execução',
                'options' => [0, 30],
                'default' => 0,
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_week' => 2,
            'min_interval_days' => 3,
        ],
        'examples' => [
            'weekly_monday' => ['day_of_week' => 'monday', 'hour' => 10, 'minute' => 0, 'description' => 'Semanal - Segunda (10:00)'],
            'weekly_friday' => ['day_of_week' => 'friday', 'hour' => 16, 'minute' => 30, 'description' => 'Semanal - Sexta (16:30)'],
        ],
    ],

    // Agendamento Mensal
    'monthly' => [
        'name' => 'Mensalmente',
        'description' => 'Executa o relatório uma vez por mês',
        'icon' => 'calendar-month',
        'config_fields' => [
            'day_of_month' => [
                'type' => 'select',
                'label' => 'Dia do Mês',
                'options' => range(1, 28),
                'default' => 1,
                'required' => true,
            ],
            'hour' => [
                'type' => 'select',
                'label' => 'Hora de Execução',
                'options' => range(8, 17),
                'default' => 9,
                'required' => true,
            ],
            'minute' => [
                'type' => 'select',
                'label' => 'Minuto de Execução',
                'options' => [0],
                'default' => 0,
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_month' => 1,
            'min_interval_days' => 25,
        ],
        'examples' => [
            'monthly_first' => ['day_of_month' => 1, 'hour' => 9, 'minute' => 0, 'description' => 'Mensal - Primeiro dia (01/09:00)'],
            'monthly_last' => ['day_of_month' => 28, 'hour' => 15, 'minute' => 0, 'description' => 'Mensal - Final do mês (28/15:00)'],
        ],
    ],

    // Agendamento Trimestral
    'quarterly' => [
        'name' => 'Trimestralmente',
        'description' => 'Executa o relatório a cada 3 meses',
        'icon' => 'calendar-quarter',
        'config_fields' => [
            'quarter_month' => [
                'type' => 'select',
                'label' => 'Mês do Trimestre',
                'options' => [
                    1 => 'Janeiro (Q1)',
                    4 => 'Abril (Q2)',
                    7 => 'Julho (Q3)',
                    10 => 'Outubro (Q4)',
                ],
                'default' => 1,
                'required' => true,
            ],
            'day_of_month' => [
                'type' => 'select',
                'label' => 'Dia do Mês',
                'options' => range(1, 28),
                'default' => 15,
                'required' => true,
            ],
            'hour' => [
                'type' => 'select',
                'label' => 'Hora de Execução',
                'options' => range(9, 16),
                'default' => 10,
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_quarter' => 1,
            'min_interval_days' => 85,
        ],
        'examples' => [
            'quarterly_q1' => ['quarter_month' => 1, 'day_of_month' => 15, 'hour' => 10, 'description' => 'Trimestral - Q1 (15/01 10:00)'],
            'quarterly_q2' => ['quarter_month' => 4, 'day_of_month' => 15, 'hour' => 10, 'description' => 'Trimestral - Q2 (15/04 10:00)'],
        ],
    ],

    // Agendamento Anual
    'yearly' => [
        'name' => 'Anualmente',
        'description' => 'Executa o relatório uma vez por ano',
        'icon' => 'calendar-year',
        'config_fields' => [
            'month' => [
                'type' => 'select',
                'label' => 'Mês do Ano',
                'options' => [
                    1 => 'Janeiro',
                    2 => 'Fevereiro',
                    3 => 'Março',
                    4 => 'Abril',
                    5 => 'Maio',
                    6 => 'Junho',
                    7 => 'Julho',
                    8 => 'Agosto',
                    9 => 'Setembro',
                    10 => 'Outubro',
                    11 => 'Novembro',
                    12 => 'Dezembro',
                ],
                'default' => 1,
                'required' => true,
            ],
            'day_of_month' => [
                'type' => 'select',
                'label' => 'Dia do Mês',
                'options' => range(1, 28),
                'default' => 15,
                'required' => true,
            ],
            'hour' => [
                'type' => 'select',
                'label' => 'Hora de Execução',
                'options' => range(9, 16),
                'default' => 10,
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_year' => 1,
            'min_interval_days' => 350,
        ],
        'examples' => [
            'yearly_q1' => ['month' => 1, 'day_of_month' => 15, 'hour' => 10, 'description' => 'Anual - Janeiro (15/01 10:00)'],
            'yearly_q3' => ['month' => 7, 'day_of_month' => 15, 'hour' => 10, 'description' => 'Anual - Julho (15/07 10:00)'],
        ],
    ],

    // Agendamento Personalizado
    'custom' => [
        'name' => 'Personalizado',
        'description' => 'Executa o relatório com intervalo personalizado',
        'icon' => 'calendar-plus',
        'config_fields' => [
            'interval' => [
                'type' => 'select',
                'label' => 'Intervalo',
                'options' => [
                    '1 day' => '1 dia',
                    '2 days' => '2 dias',
                    '3 days' => '3 dias',
                    '1 week' => '1 semana',
                    '2 weeks' => '2 semanas',
                    '1 month' => '1 mês',
                    '2 months' => '2 meses',
                    '3 months' => '3 meses',
                ],
                'default' => '1 week',
                'required' => true,
            ],
            'hour' => [
                'type' => 'select',
                'label' => 'Hora de Execução',
                'options' => range(8, 18),
                'default' => 10,
                'required' => true,
            ],
            'minute' => [
                'type' => 'select',
                'label' => 'Minuto de Execução',
                'options' => [0, 15, 30, 45],
                'default' => 0,
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_custom' => 1,
            'min_interval_custom' => '1 day',
        ],
        'examples' => [
            'custom_weekly' => ['interval' => '1 week', 'hour' => 10, 'minute' => 0, 'description' => 'Personalizado - Semanal'],
            'custom_monthly' => ['interval' => '1 month', 'hour' => 10, 'minute' => 0, 'description' => 'Personalizado - Mensal'],
        ],
    ],

    // Agendamento em Horário Comercial
    'business_hours' => [
        'name' => 'Horário Comercial',
        'description' => 'Executa durante o horário comercial (segunda a sexta)',
        'icon' => 'clock',
        'config_fields' => [
            'start_hour' => [
                'type' => 'select',
                'label' => 'Hora de Início',
                'options' => range(8, 12),
                'default' => 9,
                'required' => true,
            ],
            'end_hour' => [
                'type' => 'select',
                'label' => 'Hora de Término',
                'options' => range(14, 18),
                'default' => 17,
                'required' => true,
            ],
            'frequency' => [
                'type' => 'select',
                'label' => 'Frequência',
                'options' => [
                    'hourly' => 'A cada hora',
                    'every_2_hours' => 'A cada 2 horas',
                    'every_4_hours' => 'A cada 4 horas',
                    'daily' => 'Diariamente',
                ],
                'default' => 'daily',
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_business_hours' => 2,
            'min_interval_hours' => 1,
        ],
        'examples' => [
            'business_daily' => ['start_hour' => 9, 'end_hour' => 17, 'frequency' => 'daily', 'description' => 'Horário Comercial - Diário'],
            'business_hourly' => ['start_hour' => 9, 'end_hour' => 17, 'frequency' => 'hourly', 'description' => 'Horário Comercial - Horário'],
        ],
    ],

    // Agendamento de Fim de Semana
    'weekend' => [
        'name' => 'Fim de Semana',
        'description' => 'Executa apenas nos finais de semana',
        'icon' => 'calendar-check',
        'config_fields' => [
            'day_of_weekend' => [
                'type' => 'select',
                'label' => 'Dia do Final de Semana',
                'options' => [
                    'saturday' => 'Sábado',
                    'sunday' => 'Domingo',
                    'both' => 'Sábado e Domingo',
                ],
                'default' => 'saturday',
                'required' => true,
            ],
            'hour' => [
                'type' => 'select',
                'label' => 'Hora de Execução',
                'options' => range(9, 17),
                'default' => 10,
                'required' => true,
            ],
            'minute' => [
                'type' => 'select',
                'label' => 'Minuto de Execução',
                'options' => [0, 30],
                'default' => 0,
                'required' => true,
            ],
        ],
        'validation' => [
            'max_schedules_per_weekend' => 1,
            'min_interval_days' => 6,
        ],
        'examples' => [
            'weekend_saturday' => ['day_of_weekend' => 'saturday', 'hour' => 10, 'minute' => 0, 'description' => 'Fim de Semana - Sábado'],
            'weekend_sunday' => ['day_of_weekend' => 'sunday', 'hour' => 10, 'minute' => 0, 'description' => 'Fim de Semana - Domingo'],
        ],
    ],
];
