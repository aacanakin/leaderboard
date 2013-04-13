##Leaderboard
- A simple leaderboard implementation in php using ariba php framework
- It is a mysql with/vs nosql approach
- It uses redis for getting highscores
- It has a single ajax service call that returns top players given player_count & type
- Optionally, it returns mysql result if debug mode is on in ajax service call
- It has bot features that automatically generates user data randomly and sets them accordingly

##Installation ( Ubuntu 12.10 )
### Framework Pre-Requisites 
 - You need to have `php5`, `apache2`, `mysql-server`, `php5-mysql` packages. If you don't have these, use `apt-get install` to install them
 - Run on terminal : `chmod -R 777 path-to-project/leaderboard/tmp`
 - go to /config/db.php set your `db_user`, `db_pass`, `db_host` values
 - make sure that `Config::set('db_schema', 'leaderboard');` exists in `db.php`

### Virtual Hosts
 - Run on terminal : `cd /etc/apache2/sites-available`
 - Run : `sudo cp default leaderboard`
 - Run sudo vi(m) on leaderboard and change all text into following;
 
```
<VirtualHost *:80>
    
    ServerAdmin webmaster@local.leaderboard.com
    ServerName local.leaderboard.com
    ServerAlias www.local.leaderboard.com
    DocumentRoot /path-to-project/leaderboard
    <Directory />
        Options FollowSymLinks
        AllowOverride All
    </Directory>
    <Directory /path-to-project/leaderboard>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        allow from all
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
       
    CustomLog ${APACHE_LOG_DIR}/access.log combined

</VirtualHost>
```
 - After changing text, run `sudo vi(m) /etc/hosts`
 - Add these lines;
 
 `127.0.0.1 local.leaderboard.com`
 
 `127.0.0.1 www.local.leaderboard.com`

 - run `sudo service apache2 reload`
 - run `sudo service apache2 restart`
 - Virtual Hosts are set now on local.leaderboard.com
 
###Deployment
 - Create a database namely `leaderboard` with collation `utf-8-general-ci`
 - run : `mysql -u username -p -h localhost leaderboard < /path-to-project/leaderboard/scripts//home/ari/Desktop/jnn/projects/leaderboard/scripts/leaderboard.sql`
 - make sure that database is imported accurately

###Redis requirements
 - NOTE : This project uses `phpredis`. Please install this php extension; https://github.com/nicolasff/phpredis
 - make sure that `redis-server` package is installed. If not, use `apt-get install` to install package
 - run redis-server in terminal : `sudo redis-server /etc/redis/redis.conf`
 - test redis. run `redis-cli PING`. It should return PONG

##Usage
###BOT Functions
- Every bot functions requires http authentication. username : `bot`, pass: `test`
- Use http://local.leaderboard.com/bot/init to initialize 100 users with random name string with every experience and level value set to zero
- Use http://local.leaderboard.com/bot/simulate/{player_count} to let gain `today_exp` to randomly chosen {$player_count} players
- Use http://local.leaderboard.com/bot/change_day to move all users' `today_exp` to `yesterday_exp` and set 0 to `today_exp`
- Use http://local.leaderboard.com/bot/change_week to reset `week_exp` values of all users

###Ajax Functions
- There exists a single ajax call that returns rankings
- Use http://local.leaderboard/ajax/leadearboard/today/10 returns top 10 players ordered by `today_exp` in json format
- Use http://local.leaderboard/ajax/leaderboard/total/40 returns top 40 players ordered by `total_exp` in json format
- Use http://local.leaderboard/ajax/leaderboard/week/5/true returns top 5 players ordered by `week_exp` in debug mode
- Use http://local.leaderboard/ajax/leaderboard/yesterday/1 returns the most successful player in terms of `week_exp`
- Debug mode changes the return type
- Debug mode adds mysql execution and redis execution times to compare
- An example return type with no debugging enabled;
- URL : http://local.leaderboard.com/ajax/leaderboard/today/5 returns the following data;

```
{
    "leaderboard": [
        {
            "user_id": "97",
            "name": "KsIeSX",
            "today_exp": 672913,
            "level": 917
        },
        {
            "user_id": "58",
            "name": "EaKrl",
            "today_exp": 671042,
            "level": 1140
        },
        {
            "user_id": "64",
            "name": "ViQGVDZuu",
            "today_exp": 549257,
            "level": 1453
        },
        {
            "user_id": "8",
            "name": "oewQPKpYoT",
            "today_exp": 537762,
            "level": 1266
        },
        {
            "user_id": "55",
            "name": "qIWaREPOqk",
            "today_exp": 489068,
            "level": 911
        }
    ]
}
```

- URL : http://local.leaderboard.com/ajax/leaderboard/total/3/true returns the following data;

```
{
    "redis": {
        "leaderboard": [
            {
                "user_id": "33",
                "name": "BePOtHM",
                "total_exp": 1754875,
                "level": 1754
            },
            {
                "user_id": "36",
                "name": "HGD",
                "total_exp": 1753479,
                "level": 1753
            },
            {
                "user_id": "64",
                "name": "ViQGVDZuu",
                "total_exp": 1453688,
                "level": 1453
            }
        ],
        "debug": {
            "exec_time": "0.20480155944824 ms"
        }
    },
    "mysql": {
        "leaderboard": [
            {
                "user_id": "33",
                "name": "BePOtHM",
                "level": "1754",
                "total_exp": "1754875"
            },
            {
                "user_id": "36",
                "name": "HGD",
                "level": "1753",
                "total_exp": "1753479"
            },
            {
                "user_id": "64",
                "name": "ViQGVDZuu",
                "level": "1453",
                "total_exp": "1453688"
            }
        ],
        "debug": {
            "exec_time": "0.80704689025879 ms"
        }
    },
    "error": false
}
```
 
##Limitations
- It currently can build only 100 users. It's not dynamic because of random generation in bot functions
- Redis retrieval does not seem to bring much difference. But it is a lot faster with bigger inputs according to time complexities

##Framework Info
ariba is a lightweight php framework

it uses smarty template engine for view template operations. 

it uses php memcache extension for saving the mysql results in memory of the server machine.

wiki page : https://github.com/arascanakin/ariba/wiki/ariba-php-framework

project changelog update page : http://arascanakin.github.com/ariba/

Contribute : If you want to play with it, please fork it or send me an email to contribute.

for windows environments, please use xampp. http://www.apachefriends.org/en/xampp.html

NOTE : in wamp, there are several issues in installing framework. i don't recommend it.

version : 1.3.0
