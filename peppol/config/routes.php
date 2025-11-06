<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a URL
| normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
*/

// Public webhook routes (no authentication required)
$route['peppol/webhook'] = 'peppol/peppol_webhook/index';
$route['peppol/webhook/(:any)'] = 'peppol/peppol_webhook/$1';

// Health check endpoint
$route['peppol/health'] = 'peppol/peppol_webhook/health';