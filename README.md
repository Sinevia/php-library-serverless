[![Gitpod Ready-to-Code](https://img.shields.io/badge/Gitpod-Ready--to--Code-blue?logo=gitpod)](https://gitpod.io/#https://github.com/Sinevia/php-library-serverless) 

# PHP Library Serverless

Library to help with serverless function development

## Openwhisk ##

Sets the $_REQUEST and $_SERVER global variables from the arguments passed to the function.

```php
\Sinevia\Serverless::openwhisk($args);
```

## Session ##

Serverlsess does not support session out of the box. To replicate this functionality the following may be used together with the Sessions plugin which will save the the sessions in the database.

1. Initiate session

```php
Sinevia\Serverless::sessionStart();
```

2. Session ID

```php
\Sinevia\Serverless::sessionId();
```

3. Set Session Variable

```php
\Sinevia\Serverless::sessionSet('UserName', 'Sarah');
```

4. Get Session Variable

```php
$userName = \Sinevia\Serverless::sessionGet('UserName');
```
