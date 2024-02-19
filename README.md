# Laravel Test Kit


### Version Compatibility

| Laravel Version | This Package Version |       Branch |
|----------------:|---------------------:|-------------:|
|             v10 |                  3.x |          3.x |  
|              v9 |                  2.0 |          2.x |  
|              v8 |                  1.0 | version/v1.0 |  

See [CHANGE LOG](CHANGELOG.md) for change history.

## Installation

1. Add the repository to your `composer.json`

```
"repositories": [
    {
        "type":"vcs",
        "url":"git@bitbucket.org:elegantmedia/laravel-test-kit.git"
    }
],
```

2. Require the package through the command line

```
composer require emedia/laravel-test-kit --dev
```


## Faker Trait

``` php
use Faker;

// usage
$email = $this->faker()->email;        // defaults to Australia
$email = $this->faker('en_US')->email;
```

## InteractsWithUsers Trait

``` php
use InteractsWithUsers;

// find a user by email
$user = $this->findUserByEmail('someone@somewhere.com');

// find a user with a given role (assumes you have the 'roles' relationship set)
$user = $this->findUserWithoutRole('admin');

// find a user without given (all) roles (assumes you have the 'roles' relationship set)
$user = $this->findUserWithoutRoles('admin', 'super-admin');
$user = $this->findUserWithoutRoles(['admin', 'super-admin']);

// change the default user model
$this->setUserClass('App\Models\User');

// get the resolved user model
$model = $this->getUserModel();
```


## Testing Emails

### Test Using Array Driver

You can use the array driver to test emails. This method is only possible when using PHPUnit within the same process as the application. This is the default behaviour when running `phpunit` from the command line. You can use this method with `BrowserKit` tests as well. But this method will not work with `Dusk` tests. For `Dusk` tests, you will need to use `log` driver method mentioned below.

In your `phpunit.xml`, or `.env` file add the `array` mail driver.
```
<env name="MAIL_DRIVER" value="array" />
```

``` php
use \EMedia\TestKit\Traits\MailTracking;

public function testAdminCanLogin(): void
{
    // do something that triggers an email
    
    // verify an email was sent
    $this->seeEmailWasSent();
    
    // verify no emails sent
    $this->dontSeeEmailWasSent();
    
    // verify email count
    $this->seeEmailCount(1);
    
    // verify last email sent to address
    $this->seeLastEmailSentTo('john@example.com');
    $this->seeLastEmailSentTo(['john@example.com', 'jane@example.com]);
    
    // verify last email not sent to address
    $this->dontSeeLastEmailSentTo('john@example.com');
    $this->dontSeeLastEmailSentTo(['john@example.com', 'jane@example.com]);
    
    // verify last email recepients
    $this->seeEmailSubject('Subject Line');
    
    // verify last email contains text
    $this->seeLastEmailContains('Test Message');
    
    // verify last email don't contain text
    $this->dontSeeLastEmailContains('Test Message');
        
    // get an array of sent emails from array driver
    $emails = $this->getSentEmails();
    
    // get the last email
    $email = $this->lastEmail();
}	
```

### Test Using Log Driver

You can use the log driver to test emails. This method can be used with `Dusk`, `BrowserKit` and `PHPUnit` tests. This method will work with any test that is run in a separate process to the application.

```php
use \EMedia\TestKit\Traits\LogMailTracking;

public function testAdminCanLogin(): void
{
    // do something that triggers an email
    
    // verify an email was sent
    $this->seeEmailWasSent();
    
    // verify no emails sent
    $this->dontSeeEmailWasSent();
    
    // verify email count
    $this->seeEmailCount(1);
    
    // verify last email sent to address
    $this->seeLastEmailSentTo('john@example.com');
    $this->seeLastEmailSentTo(['john@example.com', 'jane@example.com]);
    
    // verify last email not sent to address
    $this->dontSeeLastEmailSentTo('john@example.com');
    $this->dontSeeLastEmailSentTo(['john@example.com', 'jane@example.com]);
    
    // verify last email recepients
    $this->seeEmailSubject('Subject Line');
    
    // verify last email contains text
    $this->seeLastEmailContains('Test Message');
    
    // verify last email don't contain text
    $this->dontSeeLastEmailContains('Test Message');
        
    // get an array of sent emails from array driver
    $emails = $this->getSentEmails();
    
    // get the last email
    $email = $this->lastEmail();
}	
```



## How to Test on a Separate Database

If you want to run testing on a separate database, create a separate one in `database.php`

``` php
    'mysql_testing' => [
        'driver'    => 'mysql',
        'host'      => env('DB_TESTING_HOST', config('database.connections.mysql.host')),
        'database'  => env('DB_TESTING_DATABASE', config('database.connections.mysql.database')),
        'username'  => env('DB_TESTING_USERNAME', 'forge'),
        'password'  => env('DB_TESTING_PASSWORD', ''),
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
        'strict'    => false,
    ],
```

Add the new vars in `.env`
```
DB_TESTING_DATABASE=mydb_test
DB_TESTING_USERNAME=secret
DB_TESTING_PASSWORD=secret
```

In your `phpunit.xml` add the new DB connection.
```
<env name="DB_CONNECTION" value="mysql_testing" />
```


## Copyright

Copyright (c) Elegant Media.
