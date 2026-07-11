<?php $this->start('app-side-panel') ?>

<kiss-card>
    <h2 class="kiss-size-4">Online Store</h2>

    <kiss-navlist>
        <ul>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">dashboard</icon>
                    <?= t('Dashboard') ?>
                </a>
            </li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/products') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/products') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">shopping_bag</icon>
                    <?= t('Products') ?>
                </a>
            </li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/orders') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/orders') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">receipt_long</icon>
                    <?= t('Orders') ?>
                </a>
            </li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/customers') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/customers') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">people</icon>
                    <?= t('Customers') ?>
                </a>
            </li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/promotions') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/promotions') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">local_offer</icon>
                    <?= t('Vouchers & Promos') ?>
                </a>
            </li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/suppliers') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/suppliers') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">local_shipping</icon>
                    <?= t('Suppliers & Purchasing') ?>
                </a>
            </li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/reports') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/reports') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">analytics</icon>
                    <?= t('Reports & Analytics') ?>
                </a>
            </li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/content') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/content') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">web</icon>
                    <?= t('Homepage Content') ?>
                </a>
            </li>
            <li class="kiss-nav-divider"></li>
            <li>
                <a class="kiss-flex kiss-flex-middle <?= ($this->request->route === '/store/settings') ? 'kiss-link-muted kiss-text-bold' : 'kiss-color-muted' ?>" href="<?= $this->route('/store/settings') ?>">
                    <icon class="kiss-margin-small-end" style="font-size: 20px;">settings</icon>
                    <?= t('Settings') ?>
                </a>
            </li>
        </ul>
    </kiss-navlist>
</kiss-card>

<?php $this->end('app-side-panel') ?>
