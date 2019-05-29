<?php

return [
    /**
     * Which middlewares should be applied to the route for search results.
     */ 
    'middleware' => ['web', 'auth'],

    /**
     * How many results to display per category.
     */
    'limit' => 10,

    /**
     * If you want to display a logo at the bottom of the search assign it here.
     */
    'logo' => '',

    /**
     * Categories (tables) that should be searched throught.
     *
     * Refer to the Eloquent model as the first property.
     *     
     * 'As':      The title above the results.
     * 'Route':   The route that will be called when a result is chosen (the id will be send as parameter).
     * 'Columns': Which columns to search through in the table, separated in array, enclose 2 or more columns in a
     *            sub-array to make them a combined search (concatinated).
     * 'Except':  An array listing rows that are excluded if the column matches second parameter.
     * 'Filter':  Apply customn filter callback method. Builder will be sent as parameter.
     * 'Results': Which columns should be shown at the output, enclosed in an array.
     *            'Title': The main title.
     *            'Info':  A help description below the title.
     *            Both title and info can be closures with query as parameter if you wish to output a join.
     */
    'include' => [
        App\User::class => [
            'as'      => 'Users',
            'route'   => 'users.show',
            'columns' => [['firstname', 'lastname']],
            'except'  => [
                ['active', false],
            ],
            'filter'  => function ($query) {
                // Custom filter
            },
            'results' => [
                'title' => ['firstname', 'lastname'],
                'info'  => ['email'],
            ],
        ],
    ],
];