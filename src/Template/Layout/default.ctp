<?php
/**
 * @var \App\View\AppView $this
 * @var string $h1
 */

$cakeDescription = 'CakePHP: the rapid development php framework';
?>
<!DOCTYPE html>
<html>
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>
		UnitTestRunner - <?= $this->fetch('title') ?>
	</title>
	<?= $this->Html->meta('icon') ?>

	<?= $this->Html->css('/css/bootstrap.min.css') ?>
	<?= $this->Html->script('https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js') ?>
	<?= $this->Html->script('/js/bootstrap.min.js') ?>

	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body>
<?= $this->Flash->render() ?>
<div class="container clearfix">
	<div class="starter-template">
		<?= $this->fetch('content') ?>
	</div>
</div>
<footer>
</footer>
</body>
</html>
