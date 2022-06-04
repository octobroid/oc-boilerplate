<div class="list-pagination">
    <div class="list-pagination-summary text-muted me-auto">
        <?= e(trans('backend::lang.list.pagination', ['from' => $pageFrom, 'to' => $pageTo, 'total' => $recordTotal])) ?>
    </div>
    <?php if ($pageLast > 1): ?>
        <nav class="list-pagination-links loading-indicator-container size-small">
            <ul class="pagination">
                <?php if ($pageCurrent > 1): ?>
                    <li class="page-item">
                        <a
                            href="javascript:;"
                            class="page-link page-first"
                            data-request="<?= $this->getEventHandler('onPaginate') ?>"
                            data-request-data="page: 1"
                            data-load-indicator="<?= e(trans('backend::lang.list.loading')) ?>"
                            title="<?= e(trans('backend::lang.list.first_page')) ?>">
                            <i class="icon-angle-double-left"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span
                            class="page-link page-first"
                            title="<?= e(trans('backend::lang.list.first_page')) ?>">
                            <i class="icon-angle-double-left"></i>
                        </span>
                    </li>
                <?php endif ?>
                <?php if ($pageCurrent > 1): ?>
                    <li class="page-item">
                        <a
                            href="javascript:;"
                            class="page-link page-back"
                            data-request="<?= $this->getEventHandler('onPaginate') ?>"
                            data-request-data="page: <?= $pageCurrent-1 ?>"
                            data-load-indicator="<?= e(trans('backend::lang.list.loading')) ?>"
                            title="<?= e(trans('backend::lang.list.prev_page')) ?>">
                            <i class="icon-angle-left"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span
                            class="page-link page-back"
                            title="<?= e(trans('backend::lang.list.prev_page')) ?>">
                            <i class="icon-angle-left"></i>
                        </span>
                    </li>
                <?php endif ?>
                <?php foreach ($recordElements as $element): ?>
                    <?php if (is_string($element)): ?>
                        <li class="page-item disabled">
                            <span class="page-link page-dots">
                                <?= e($element) ?>
                            </span>
                        </li>
                    <?php elseif (is_array($element)): ?>
                        <?php foreach ($element as $page => $url): ?>
                            <?php if ($page === $pageCurrent): ?>
                                <li class="page-item active">
                                    <span class="page-link page-active">
                                        <?= $page ?>
                                    </span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a
                                        href="javascript:;"
                                        class="page-link page-back"
                                        data-request="<?= $this->getEventHandler('onPaginate') ?>"
                                        data-request-data="page: <?= $page ?>"
                                        data-load-indicator="<?= e(trans('backend::lang.list.loading')) ?>"
                                    >
                                        <?= $page ?>
                                    </a>
                                </li>
                            <?php endif ?>
                        <?php endforeach ?>
                    <?php endif ?>
                <?php endforeach ?>
                <?php if ($pageLast > $pageCurrent): ?>
                    <li class="page-item">
                        <a
                            href="javascript:;"
                            class="page-link page-next"
                            data-request-data="page: <?= $pageCurrent+1 ?>"
                            data-request="<?= $this->getEventHandler('onPaginate') ?>"
                            data-load-indicator="<?= e(trans('backend::lang.list.loading')) ?>"
                            title="<?= e(trans('backend::lang.list.next_page')) ?>">
                            <i class="icon-angle-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span
                            class="page-link page-next"
                            title="<?= e(trans('backend::lang.list.next_page')) ?>">
                            <i class="icon-angle-right"></i>
                        </span>
                    </li>
                <?php endif ?>
                <?php if ($pageLast > $pageCurrent): ?>
                    <li class="page-item">
                        <a
                            href="javascript:;"
                            class="page-link page-last"
                            data-request-data="page: <?= $pageLast ?>"
                            data-request="<?= $this->getEventHandler('onPaginate') ?>"
                            data-load-indicator="<?= e(trans('backend::lang.list.loading')) ?>"
                            title="<?= e(trans('backend::lang.list.last_page')) ?>">
                            <i class="icon-angle-double-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span
                            class="page-link page-last"
                            title="<?= e(trans('backend::lang.list.last_page')) ?>">
                            <i class="icon-angle-double-right"></i>
                        </span>
                    </li>
                <?php endif ?>
            </ul>
        </nav>
    <?php endif ?>
</div>
