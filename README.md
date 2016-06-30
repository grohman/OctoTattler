# OctoTattler

**Description:**
This code allows you to send async notifications to users with web-socket. This is version for Laravel-based OctoberCMS.

-------
**Usage examples:** 
[https://youtu.be/yCIuFlBCCGA](https://youtu.be/yCIuFlBCCGA)

    Tattler::say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For anyone']);

    Tattler::room('broadcast')->say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For anyone']);

    Tattler::room('Backend\Models\User')->say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For anyone at Users listing page']);

    Tattler::user(Backend\Models\User::first())->say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For backend admin']);

    Tattler::currentUser()->say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For current user']);

-------
Adding new js handlers:

    window.tattler.addHandler('mySuperHandler', function(data){ console.log(data); })

Then from php run `Tattler::say(['handler'=>'mySuperHandler', 'anything'=>['else'], [1,2,3]]);`

-------
**Installation**

 Install and run Tattler backend: https://github.com/grohman/tattler
 Then `git clone https://github.com/grohman/OctoTattler.git plugins/grohman/tattler`
   or
    `git submodle init && git submodule add https://github.com/grohman/OctoTattler plugins/grohman/tattler`

   

echo TATTLER_SERVER=tattler.yourdomain.tld >> .env

cd plugins/grohman/tattler

composer install

cd -

php artisan october:up

Add 'Tattler' => Grohman\Tattler\Facades\Tattler::class to config/app.php in section 'aliases'











