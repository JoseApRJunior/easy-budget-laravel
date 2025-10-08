<?php
return [ 
    'name'        => [ 
        'notEmpty' => 'Product name is required',
        'length'   => 'Name must be between 1 and 255 characters long'
    ],
    'price'       => [ 
        'notEmpty' => 'Price is mandatory',
        'numeric'  => 'Price must be a numeric value',
        'positive' => 'Price must be greater than zero'
    ],
    'stock'       => [ 
        'notEmpty' => 'Stock is required',
        'intVal'   => 'Stock must be an integer',
        'min'      => 'Stock cannot be negative'
    ],
    'active'      => [ 
        'in' => 'Invalid status'
    ],
    'description' => [ 
        'length' => 'Description must be at most 1000 characters'
    ],
    'image'       => [ 
        'image'    => 'File must be a valid image',
        'size'     => 'Image must be a maximum of 2MB',
        'mimetype' => 'Invalid image format. Use JPG or PNG '
    ]
];
