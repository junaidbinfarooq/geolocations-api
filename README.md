# geolocations-api

The app is a Symfony-based API that lets a user retrieve distances between various geolocations. It exposes a command
that lets you do it as described below. Behind the scenes, the app uses [position stack](https://positionstack.com/) API
to resolve various
addresses to their respective coordinates. To use the app, please follow the following instructions:

## Installation:

1. First clone this repository using the command `git clone https://github.com/junaidbinfarooq/geolocations-api.git`
2. Move into the new directory using the command `cd geolocations-api`
3. Install the dependencies using the command `composer install`
4. To make API requests to position stack API, an API key would be needed. For that, you would need an account on the
   said API site.
5. Copy the `.env` file using the command `cp .env .env.dev.local` and add the API key to the environment variable
   named `API_KEY`

## Running the app:

1. After installation is done, start the Symfony local web server using the command `symfony serve -d`. This command
   starts and runs a local web server in the background. There might be a warning about installing a certificate to run
   the web server with TLS i.e., _https://_. This is an optional step that won't impact the starting of the server and
   can thus be ignored.
2. The above command will likely start a web server on `http://127.0.0.1:8000` unless some other instance of a web
   the server is using the same address.
3. The app, as mentioned at the top, exposes a command that retrieves the distances between the geolocations and writes
   the same to a csv file. Run `php bin/console app:get-distances` to run this command and wait a bit till the API
   requests are made. The remote API may take a bit of time or return with an error which should be possible to work
   around by simply retrying the request. For the sake of simplicity, the app uses pre-defined geolocations.
4. The app uses a caching mechanism to make subsequent requests very performant. This means a bunch of requests made
   more than once should, on subsequent tries, take less than 20 _milliseconds_ to process. For the sake of simplicity,
   the cache is stored locally in the `var/cache/dev/pools/app` _dir_ of the project. Use the command
   `php bin/console cache:pool:clear cache.app` to clear the cache and get new results.
5. The app also comes with logging support which means any errors (though it can be some other thing also) are logged to
   the console. The logs are stored on the disk by default and can be found in `var/log` _dir_ of the project.
6. The command prints various information like success/error messages, distances, etc., to the console.

## Testing:

The app contains a couple of unit test cases which could be executed byt running the command `composer test`.