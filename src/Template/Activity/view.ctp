<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\PhpTestActivity $activity
 */

$elMinutes = floor($activity->elapsed_seconds / 60);
$elSeconds = ceil($activity->elapsed_seconds - $elMinutes * 60);

$testSteps = !is_array($activity->content)? json_decode($activity->content, true): $activity->content;
$title = 'Тест ветки ' . $activity->PhpTests->ref . ' репозитория ' . $activity->PhpTests->repository;
$this->assign('title', $title);
?>
<h1><?= $title ?>
	<small><br/>Создан <?= $activity->PhpTests->created->format('d/m/y H:i'); ?>, а
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
		<?= $this->Form->create(null, ['type' => 'put', 'url' => '/tests/edit/' . $activity->PhpTests->id]); ?>
		<?= $this->Form->hidden('rerun_test', ['value' => 1]); ?>
		<?= $this->Form->hidden('redirect', ['value' => 1]); ?>
		<?= $this->Form->submit('Выполнить тест заново', ['class' => 'btn btn-warning']); ?>
		<?= $this->Form->end(); ?>
	</div>
</div>
