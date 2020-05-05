<?php
use Fineweb\Wirecard\Helper\Helper;
$helper  = app(Helper::class);
?>
<p><a href="<?= $helper->getBoletoLink($order) ?>" target="_blank">Imprimir Boleto</a></p>