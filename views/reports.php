<?php

?>

<vue-view>
    <template>
        <div class="kiss-margin-large-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?=$this->route('/store')?>"><?=t('Store')?></a></li>
                        <li><span><?=t('Reports & Analytics')?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">Laporan Penjualan & Analytics</h3>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else>
                <kiss-grid cols="1 2@m" gap="medium">
                    
                    <kiss-card theme="bordered contrast" class="kiss-padding-large">
                        <div class="kiss-text-bold kiss-size-medium kiss-margin-bottom">Daily Revenues Summary</div>
                        
                        <div v-if="Object.keys(report.daily).length" class="kiss-margin-top">
                            <div v-for="(amount, date) in report.daily" :key="date" class="kiss-margin-small-bottom">
                                <div class="kiss-flex kiss-flex-between kiss-size-xsmall kiss-margin-xsmall-bottom">
                                    <span>{{ date }}</span>
                                    <span class="kiss-text-bold">{{ formatCurrency(amount) }}</span>
                                </div>
                                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden;">
                                    <div :style="{ width: getDailyPercentage(amount) + '%' }" style="height: 100%; background: linear-gradient(90deg, #3a86ff, #06d6a0); border-radius: 4px;"></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="kiss-padding kiss-align-center kiss-color-muted">
                            No settled sales logged yet.
                        </div>
                    </kiss-card>

                    
                    <kiss-card theme="bordered contrast" class="kiss-padding-large">
                        <div class="kiss-text-bold kiss-size-medium kiss-margin-bottom">Top-Selling Products (by Revenue)</div>
                        
                        <div v-if="Object.keys(report.products).length" class="kiss-margin-top">
                            <div v-for="(amount, name) in report.products" :key="name" class="kiss-margin-small-bottom">
                                <div class="kiss-flex kiss-flex-between kiss-size-xsmall kiss-margin-xsmall-bottom">
                                    <span class="kiss-text-bold">{{ name }}</span>
                                    <span>{{ formatCurrency(amount) }}</span>
                                </div>
                                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden;">
                                    <div :style="{ width: getProductPercentage(amount) + '%' }" style="height: 100%; background: linear-gradient(90deg, #8338ec, #ff007f); border-radius: 4px;"></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="kiss-padding kiss-align-center kiss-color-muted">
                            No products sold yet.
                        </div>
                    </kiss-card>
                </kiss-grid>
            </div>
        </div>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    report: {
                        daily: {},
                        products: {}
                    },
                    loading: true
                }
            },

            mounted() {
                this.loadReports();
            },

            methods: {
                loadReports() {
                    this.loading = true;
                    this.$request('/store/getReportsData').then(res => {
                        this.report = res || { daily: {}, products: {} };
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error generating sales reports', 'danger');
                    });
                },
                formatCurrency(value) {
                    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
                },
                getDailyPercentage(amount) {
                    const max = Math.max(...Object.values(this.report.daily), 1);
                    return (amount / max) * 100;
                },
                getProductPercentage(amount) {
                    const max = Math.max(...Object.values(this.report.products), 1);
                    return (amount / max) * 100;
                }
            }
        }
    </script>
</vue-view>

<?=$this->render('store:views/partials/sidebar.php')?>
