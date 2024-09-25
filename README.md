# fluidlabs-api
Fluidlabs CRM Backend Using Laravel 10 


## installation

1 Install Dependancy 
<pre>composer install </pre>

2 Install Migrations
<pre>php artisan migrate </pre>

<pre>php artisan db:seed --class=PermissionTableSeeder</pre>

<pre>php artisan db:seed --class=CreateAdminUserSeeder</pre>

<pre>php artisan passport:install</pre>

3 Initialize laravel Project
<pre>php artisan Serve </pre>