<?php use Fisharebest\Webtrees\Auth; ?>
<?php use Fisharebest\Webtrees\Bootstrap4; ?>
<?php use Fisharebest\Webtrees\FontAwesome; ?>
<?php use Fisharebest\Webtrees\Functions\FunctionsPrint; ?>
<?php use Fisharebest\Webtrees\Functions\FunctionsPrintFacts; ?>
<?php use Fisharebest\Webtrees\Functions\FunctionsPrintLists; ?>
<?php use Fisharebest\Webtrees\GedcomTag; ?>
<?php use Fisharebest\Webtrees\Html; ?>
<?php use Fisharebest\Webtrees\I18N; ?>

<h2 class="wt-page-title">
	<?= $media->getFullName() ?>
</h2>

<div class="wt-page-content">
	<ul class="nav nav-tabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" data-toggle="tab" role="tab" href="#details">
				<?= I18N::translate('Details') ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= empty($individuals) ? ' text-muted' : '' ?>" data-toggle="tab" role="tab" href="#individuals">
				<?= I18N::translate('Individuals') ?>
				<?= Bootstrap4::badgeCount($individuals) ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= empty($families) ? ' text-muted' : '' ?>" data-toggle="tab" role="tab" href="#families">
				<?= I18N::translate('Families') ?>
				<?= Bootstrap4::badgeCount($families) ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= empty($sources) ? ' text-muted' : '' ?>" data-toggle="tab" role="tab" href="#sources">
				<?= I18N::translate('Sources') ?>
				<?= Bootstrap4::badgeCount($sources) ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= empty($notes) ? ' text-muted' : '' ?>" data-toggle="tab" role="tab" href="#notes">
				<?= I18N::translate('Notes') ?>
				<?= Bootstrap4::badgeCount($notes) ?>
			</a>
		</li>
	</ul>

	<div class="tab-content mt-4">
		<div class="tab-pane active fade show" role="tabpanel" id="details">
			<table class="table wt-facts-table">
				<?php foreach ($media->mediaFiles() as $media_file): ?>
				<tr>
					<th scope="row">
						<?= I18N::translate('Media file') ?>
						<div class="editfacts">
							<?= FontAwesome::linkIcon('edit', I18N::translate('Edit'), ['class' => 'btn btn-link', 'href' => 'edit_interface.php?action=media-edit&xref=' . $media->getXref() . '&fact_id=' . $media_file->factId() . '&ged=' . e($media->getTree()->getName())]) ?>
							<?php if (count($media->mediaFiles()) > 1): ?>
								<?= FontAwesome::linkIcon('delete', I18N::translate('Delete'), ['class' => 'btn btn-link', 'href' => '#', 'onclick' => 'return delete_fact("' . I18N::translate('Are you sure you want to delete this fact?') . '", "' . $media->getXref() . '", "' . $media_file->factId() . '");']) ?>
							<?php endif ?>
						</div>
					</th>
					<td class="d-flex justify-content-between">
						<div>
							<?php if (Auth::isEditor($media->getTree())): ?>
								<?= GedcomTag::getLabelValue('FILE', $media_file->filename()) ?>
								<?php if ($media_file->fileExists()): ?>
									<?php if ($media->getTree()->getPreference('SHOW_MEDIA_DOWNLOAD') >= Auth::accessLevel($media->getTree())): ?>
									— <a href="<?= $media_file->imageUrl(0, 0, '') ?>">
											<?= I18N::translate('Download file') ?>
										</a>
									<?php endif ?>
								<?php else: ?>
									<p class="alert alert-danger">
										<?= I18N::translate('The file “%s” does not exist.', $media_file->filename()) ?>
									</p>
								<?php endif ?>

							<?php endif ?>
							<?= GedcomTag::getLabelValue('TITL', $media_file->title()) ?>
							<?= GedcomTag::getLabelValue('TYPE', $media_file->type()) ?>
							<?= GedcomTag::getLabelValue('FORM', $media_file->format()) ?>
						</div>
						<div>
							<?= $media_file->displayImage(200, 150, 'contain', []) ?>
						</div>
					</td>
				</tr>
				<?php endforeach ?>
				<?php foreach ($facts as $fact): ?>
					<?php FunctionsPrintFacts::printFact($fact, $media) ?>
				<?php endforeach ?>
				<?php if ($media->canEdit()): ?>
					<?php FunctionsPrint::printAddNewFact($media->getXref(), $facts, 'OBJE') ?>
					<tr>
						<th>
							<?= I18N::translate('Source') ?>
						</th>
						<td>
							<a href="<?= e(Html::url('edit_interface.php', ['action' => 'add', 'ged' => $media->getTree()->getName(), 'xref' => $media->getXref(), 'fact' => 'SOUR'])) ?>">
								<?= I18N::translate('Add a source citation') ?>
							</a>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?= I18N::translate('Shared note') ?>
						</th>
						<td>
							<a href="<?= e(Html::url('edit_interface.php', ['action' => 'add', 'ged' => $media->getTree()->getName(), 'xref' => $media->getXref(), 'fact' => 'SHARED_NOTE'])) ?>">
								<?= I18N::translate('Add a shared note') ?>
							</a>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?= I18N::translate('Restriction') ?>
						</th>
						<td>
							<a href="<?= e(Html::url('edit_interface.php', ['action' => 'add', 'ged' => $media->getTree()->getName(), 'xref' => $media->getXref(), 'fact' => 'RESN'])) ?>">
								<?= I18N::translate('Add a restriction') ?>
							</a>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?= I18N::translate('Media file') ?>
						</th>
						<td>
							<a href="#" data-href="<?= e(route('add-media-file', ['ged' => $media->getTree()->getName(), 'xref' => $media->getXref()])) ?>" data-target="#wt-ajax-modal" data-toggle="modal">
								<?= I18N::translate('Add a media file') ?>
							</a>
						</td>
					</tr>
				<?php endif ?>
			</table>
		</div>

		<div class="tab-pane fade" role="tabpanel" id="individuals">
			<?= FunctionsPrintLists::individualTable($individuals) ?>
		</div>

		<div class="tab-pane fade" role="tabpanel" id="families">
			<?= FunctionsPrintLists::familyTable($families) ?>
		</div>

		<div class="tab-pane fade" role="tabpanel" id="sources">
			<?= FunctionsPrintLists::sourceTable($sources) ?>
		</div>

		<div class="tab-pane fade" role="tabpanel" id="notes">
			<?= FunctionsPrintLists::noteTable($notes) ?>
		</div>
	</div>
</div>

<?= view('modals/ajax') ?>