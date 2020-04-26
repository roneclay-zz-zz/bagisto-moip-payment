<?php
use Fineweb\Wirecard\Payment\Wirecard;

/** @var Wirecard $wirecard */
$wirecard = app(Wirecard::class);
$wirecard->init();
$response = $wirecard->send();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Pagamento Wirecard</title>
    <script type="text/javascript" src="{{ $wirecard->getJavascriptUrl() }}"></script>
</head>
<body>
<script type="text/javascript">
    var code = '<?= $response->getCode() ?>'
    location.href= '<?= $wirecard->getWirecardUrl() ?>' + code;
</script>
</body>
</html>