<?php

if ( !function_exists('file_cache') ) {
    /**
     * Returns path and query parameter(s) of the current request URL
     * @return array
     */
    function get_query_params(): array {
        $url_obj      = parse_url($_SERVER['REQUEST_URI']);
        $path         = $url_obj['path'];
        $query        = $url_obj['query'] ?? null;
        $query_params = [];

        // separate query parameters
        parse_str($query, $query_params);

        return [
            'path'         => $path,
            'query_params' => $query_params
        ];
    }
}
