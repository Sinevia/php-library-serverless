# PHP Library Serverless

Library to help with serverless function development

## Openwhisk ##

Sinevia\Serverless::openwhisk($args);

## Session ##

When working with sessions the Sessions plugin must be used. The sessions are saved in the database.

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
