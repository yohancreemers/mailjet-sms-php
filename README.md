# mailjet-sms-php
Simple PHP wrapper for the Mailjet API for sending sms

### Compatibility and depedencies

- This wrapper requires **PHP v5.4** or higher.
- This wrapper requires **cUrl**.

### Documentation

Official documentation for the API at https://dev.mailjet.com/sms/guides/.

The SMS API the authorization is based on a Bearer token. Signup with Mailjet to get a token https://app.mailjet.com/signup.

### Example code

```php
requireonce('mailjetsms.php');

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
