# mailjet-sms-php
Simple PHP wrapper for the Mailjet API for sending sms.

### Compatibility and depedencies

- This wrapper requires **PHP v5.4** or higher.
- This wrapper requires **cUrl**.

### Documentation

Official documentation for the API at https://dev.mailjet.com/sms/guides/.

The SMS API the authorization is based on a Bearer token. Signup with Mailjet to get a token https://app.mailjet.com/signup.

Make sure that tour text message are [properly encoded](https://dev.mailjet.com/sms/guides/encoding/).

### Example code

```php
require_once('mailjetsms.php');

//create a new instance
$apikey = '01234567890abcdef01234567890abcdef';
$oApi = new Mailjetsms($apikey);

//Send an SMS
$aData = [
  'from': 'Your sender ID',
  'to': '+31600000000',
  'text': 'Your short message',
];

$oResponse = $oApi->send($aData);

if( $oResponse->success ){
  printf('Message %s is on the way to %s.', $oResponse->MessageId, $oResponse->To);
}else{
  var_dump($oResponse);
}
```

### Optional configuration

It's possible to set a default 'from' and a default 'countrycode'.

```php
$oApi = new Mailjetsms($apikey);
$oApi->from = 'Your sender ID';
$oApi->countrycode = '31';

$aData = [
  'to': '06-12345678',
  'text': 'Your short message',
];

$oResponse = $oApi->send($aData);
```
