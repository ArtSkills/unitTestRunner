<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\Entity $activity
 */

$elMinutes = floor($activity->elapsed_seconds / 60);
$elSeconds = ceil($activity->elapsed_seconds - $elMinutes * 60);

$testSteps = json_decode($activity->content, true);
$title = 'Тест ветки ' . $activity->php_test->ref . ' репозитория ' . $activity->php_test->repository;
$this->assign('title', $title);
?>
<h1><?= $title ?>
	<small><br/>Создан <?= $activity->php_test->created->format('d/m/y H:i'); ?>, а
		выполнялся <?= $elMinutes > 0 ? $elMinutes . ' мин' : '' ?>
		<?= $elSeconds > 0 ? $elSeconds . ' сек' : '' ?>.
	</small>
</h1>
<div>
	<?php foreach ($testSteps as $testStep) { ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?= number_format($testStep['elapsedTime'], 2, ',', ''); ?>
				сек. <?= $testStep['header']; ?></div>
			<div class="panel-body">
				<?= $testStep['report']; ?>
			</div>
		</div>
	<?php } ?>

	<div>
		<?= $this->Form->create(null, ['type' => 'put', 'url' => '/tests/edit/' . $activity->php_test->id]); ?>
		<?= $this->Form->hidden('rerun_test', ['value' => 1]); ?>
		<?= $this->Form->hidden('redirect', ['value' => 1]); ?>
		<?= $this->Form->submit('Выполнить тест заново', ['class' => 'btn btn-warning']); ?>
		<?= $this->Form->end(); ?>
	</div>
</div>
