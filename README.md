git clone https://github.com/grohman/OctoTattler.git plugins/grohman/tattler

or

git submodule add https://github.com/grohman/OctoTattler plugins/grohman/tattler

echo TATTLER_SERVER=tattler.yourdomain.tld >> .env

cd plugins/grohman/tattler

composer install
