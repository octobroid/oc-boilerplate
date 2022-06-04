<?php foreach ($imageUrls as $imageUrl): ?>
    <span class="list-image-thumb <?= $isDefaultSize ? 'is-default-size' : '' ?>">
        <img src="<?= $imageUrl ?>" width="<?= $width ?>" height="<?= $height ?>" alt="" />
    </span>
<?php endforeach ?>
