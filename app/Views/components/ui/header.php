<div class="header-body">
    <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
            <h6 class="h2 text-white d-inline-block mb-0">
                <?php echo $title; ?>
            </h6>
            <?php if (!empty($breadcrumb)): ?>
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                    <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                        <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i></a></li>
                        <?php foreach ($breadcrumb as $label => $link): ?>
                            <?php if (is_numeric($label)): ?>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?php echo $link; ?>
                                </li>
                            <?php else: ?>
                                <li class="breadcrumb-item"><a href="<?php echo $link; ?>">
                                        <?php echo $label; ?>
                                    </a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>
        <div class="col-lg-6 col-5 text-right">
            <?php foreach ($actions as $action): ?>
                <?php
                $actionLink = $action['link'] ?? ($action['url'] ?? '#');
                $actionText = $action['text'] ?? ($action['label'] ?? '');
                ?>
                <a href="<?php echo $actionLink; ?>"
                    class="btn btn-sm <?php echo $action['class'] ?? 'btn-neutral'; ?>">
                    <?php if (isset($action['icon'])): ?><i class="<?php echo $action['icon']; ?> mr-1"></i>
                    <?php endif; ?>
                    <?php echo $actionText; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>