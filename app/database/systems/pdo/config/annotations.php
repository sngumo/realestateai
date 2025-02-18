<?php
/* 
 * This is the full list of annotations that can be used in the element schema
 */

return [
    'table' => [
        'engine', //designates the table storage engine InnoDb or MyISAM
        'collation' //designates the collation type for the table
    ],
    'columns' => [
        'var', //defines the table column type. Accepts either a JSON array with all set attributes
               // or a string designating the column as a int, text, longtext etc
        'primary', //defines column as primary key
        'foreign', //designates column as one-to-one, one-to-many, many-to-one, many-to-many
        'unique', //designates a unique column
        'comment' //adds a comment to the column
    ]
];
