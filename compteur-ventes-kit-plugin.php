<?php
/**
 * Plugin Name: Compteur de Ventes Kits
 * Description: Gère les ventes de différents kits et calcule un débit valorisé.
 * Version: 1.1
 * Author: Hadrien Samouillan
 */

if (!defined('ABSPATH')) exit;

register_activation_hook(__FILE__, ['CompteurDeVentesKits', 'activate']);

class CompteurDeVentesKits {
    private $kits_option = 'kits_config';
    private $ventes_table_name;
    private $debit_shortcode_used = false;

    public function __construct() {
        global $wpdb;
        $this->ventes_table_name = $wpdb->prefix . 'compteur_ventes_kits_ventes';

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_shortcode('nombre_facades_respectees', [$this, 'shortcode_facades']);
        add_shortcode('debit_valorise', [$this, 'shortcode_debit']);

        add_action('wp_footer', [$this, 'print_debit_script_in_footer']);
        add_action('wp_ajax_get_debit_valorise', [$this, 'ajax_get_debit_valorise']);
        add_action('wp_ajax_nopriv_get_debit_valorise', [$this, 'ajax_get_debit_valorise']);

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), function(
            $links
        ) {
            $doc_url = 'https://github.com/hadrien-samouillan/compteur-ventes-kits#readme';
            $links[] = '<a href="' . esc_url($doc_url) . '" target="_blank">Documentation</a>';
            return $links;
        });
    }

    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'compteur_ventes_kits_ventes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            kit_id varchar(255) NOT NULL,
            date datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function register_admin_menu() {
        add_menu_page(
            'Gestion des Ventes',
            'Compteur Ventes',
            'manage_options',
            'compteur-ventes',
            [$this, 'render_ventes_page'],
            'dashicons-chart-area',
            26
        );

        add_submenu_page(
            'compteur-ventes',
            'Configuration des Kits',
            'Configuration Kits',
            'manage_options',
            'compteur-kits-config',
            [$this, 'render_kits_page']
        );
    }

    public function render_kits_page() {
        if (isset($_POST['submit_new_kit'])) {
            if (!isset($_POST['new_kit_nonce']) || !wp_verify_nonce($_POST['new_kit_nonce'], 'add_new_kit_action')) {
                wp_die('La vérification de sécurité a échoué.');
            }

            $new_kit_data = $_POST['new_kit'] ?? [];
            $kit_id = sanitize_key($new_kit_data['id']);

            if (empty($kit_id)) {
                echo '<div class="error"><p>Le Kit ID ne peut pas être vide.</p></div>';
            } else {
                $kits = get_option($this->kits_option, []);
                if (isset($kits[$kit_id])) {
                    echo '<div class="error"><p>Ce Kit ID existe déjà. Veuillez en choisir un autre.</p></div>';
                } else {
                    $kits[$kit_id] = [
                        'nom'       => sanitize_text_field($new_kit_data['nom']),
                        'debit'     => floatval($new_kit_data['debit']),
                        'constante' => floatval($new_kit_data['constante']),
                    ];
                    update_option($this->kits_option, $kits);
                    echo '<div class="updated"><p>Nouveau kit ajouté avec succès.</p></div>';
                }
            }
        }

        if (isset($_POST['submit_kits'])) {
            if (!isset($_POST['kits_nonce']) || !wp_verify_nonce($_POST['kits_nonce'], 'save_kits_action')) {
                wp_die('La vérification de sécurité a échoué.');
            }
            $updated_kits_data = $_POST['kits'] ?? [];
            $sanitized_kits = [];
            foreach ($updated_kits_data as $kit_id => $kit_data) {
                $sanitized_id = sanitize_key($kit_id); 
                $sanitized_kits[$sanitized_id] = [
                    'nom'       => sanitize_text_field($kit_data['nom']),
                    'debit'     => floatval($kit_data['debit']),
                    'constante' => floatval($kit_data['constante']),
                ];
            }
            update_option($this->kits_option, $sanitized_kits);
            echo '<div class="updated"><p>Kits mis à jour.</p></div>';
        }

        $kits = get_option($this->kits_option, []);

        ?>
        <div class="wrap">
            <h1>Configuration des kits</h1>

            <h2>Kits existants</h2>
            <form method="post">
                <?php wp_nonce_field('save_kits_action', 'kits_nonce'); ?>
                <table class="widefat">
                    <thead><tr><th>Kit ID</th><th>Nom</th><th>Débit (m³/h)</th><th>Constante de division</th></tr></thead>
                    <tbody>
                    <?php if (!empty($kits)): ?>
                        <?php foreach ($kits as $kit_id => $kit): ?>
                            <tr>
                                <td><strong><?php echo esc_html($kit_id); ?></strong></td>
                                <td><input type="text" name="kits[<?php echo esc_attr($kit_id); ?>][nom]" value="<?php echo esc_attr($kit['nom'] ?? ''); ?>" class="regular-text"/></td>
                                <td><input type="number" step="0.01" name="kits[<?php echo esc_attr($kit_id); ?>][debit]" value="<?php echo esc_attr($kit['debit'] ?? 0); ?>" class="small-text"/></td>
                                <td><input type="number" step="0.01" name="kits[<?php echo esc_attr($kit_id); ?>][constante]" value="<?php echo esc_attr($kit['constante'] ?? '4.0'); ?>" class="small-text"/></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Aucun kit configuré pour le moment. Utilisez le formulaire ci-dessous pour en ajouter un.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <?php if (!empty($kits)): ?>
                    <p><input type="submit" name="submit_kits" class="button button-primary" value="Enregistrer les modifications"></p>
                <?php endif; ?>
            </form>

            <hr/>

            <h2>Ajouter un nouveau kit</h2>
            <form method="post">
                <?php wp_nonce_field('add_new_kit_action', 'new_kit_nonce'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="new_kit_id">Kit ID</label></th>
                            <td>
                                <input type="text" id="new_kit_id" name="new_kit[id]" required class="regular-text"/>
                                <p class="description">Identifiant unique pour le kit (ex: KIT-001). Ne peut pas être modifié par la suite.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_kit_nom">Nom</label></th>
                            <td><input type="text" id="new_kit_nom" name="new_kit[nom]" required class="regular-text"/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_kit_debit">Débit (m³/h)</label></th>
                            <td><input type="number" step="0.01" id="new_kit_debit" name="new_kit[debit]" required class="regular-text"/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_kit_constante">Constante de division</label></th>
                            <td><input type="number" step="0.01" id="new_kit_constante" name="new_kit[constante]" value="4.0" required class="regular-text"/></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button('Ajouter le kit', 'primary', 'submit_new_kit'); ?>
            </form>
        </div>
        <?php
    }

    public function render_ventes_page() {
        global $wpdb;

        if (isset($_GET['delete_vente']) && isset($_GET['_wpnonce'])) {
            $vente_id = intval($_GET['delete_vente']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_vente_' . $vente_id)) {
                $wpdb->delete($this->ventes_table_name, ['id' => $vente_id], ['%d']);
                echo '<div class="updated"><p>Vente supprimée.</p></div>';
            } else {
                wp_die('La vérification de sécurité a échoué.');
            }
        }

        if (isset($_POST['submit_ventes'])) {
            if (!isset($_POST['ventes_nonce']) || !wp_verify_nonce($_POST['ventes_nonce'], 'save_ventes_action')) {
                wp_die('La vérification de sécurité a échoué.');
            }
            
            $wpdb->insert(
                $this->ventes_table_name,
                [
                    'kit_id' => sanitize_text_field($_POST['vente_kit_id']),
                    'date'   => sanitize_text_field($_POST['vente_date']),
                ],
                [
                    '%s',
                    '%s',
                ]
            );

            echo '<div class="updated"><p>Vente ajoutée.</p></div>';
        }

        global $wpdb;
        $kits = get_option($this->kits_option, []);
        $ventes = $wpdb->get_results("SELECT id, kit_id, date FROM $this->ventes_table_name ORDER BY date DESC");

        ?>
        <div class="wrap">
            <h1>Gestion des Ventes</h1>

            <h2>Ajouter une vente</h2>
            <form method="post">
                <?php wp_nonce_field('save_ventes_action', 'ventes_nonce'); ?>
                <p>
                    <label>Kit :</label>
                    <select name="vente_kit_id" required>
                        <?php
                        $kits_existants = get_option($this->kits_option, []);
                        foreach ($kits_existants as $kit_id => $kit) {
                            echo '<option value="' . esc_attr($kit_id) . '">' . esc_html($kit['nom']) . ' (' . esc_html($kit_id) . ')</option>';
                        }
                        ?>
                    </select>
                </p>
                <p>
                    <label>Date de vente :</label>
                    <input type="datetime-local" name="vente_date" required />
                </p>
                <p><input type="submit" name="submit_ventes" class="button button-primary" value="Ajouter la vente"></p>
            </form>

            <h2>Ventes par kit</h2>
            <?php
            $ventes_par_kit = $wpdb->get_results(
                "SELECT kit_id, COUNT(id) as total_ventes FROM {$this->ventes_table_name} GROUP BY kit_id ORDER BY kit_id"
            );
            ?>
            <table class="widefat">
                <thead><tr><th>Kit</th><th>Nombre de ventes</th></tr></thead>
                <tbody>
                    <?php if (!empty($ventes_par_kit)): ?>
                        <?php foreach ($ventes_par_kit as $vente_groupee): ?>
                            <tr>
                                <td>
                                    <?php
                                    $kit_nom = isset($kits[$vente_groupee->kit_id]) ? $kits[$vente_groupee->kit_id]['nom'] : 'Kit inconnu';
                                    echo esc_html($kit_nom) . ' (' . esc_html($vente_groupee->kit_id) . ')';
                                    ?>
                                </td>
                                <td><?php echo esc_html($vente_groupee->total_ventes); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">Aucune vente pour le moment.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Journal des ventes</h2>
            <table class="widefat">
                <thead><tr><th>Kit ID</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($ventes)): ?>
                    <?php foreach ($ventes as $vente): ?>
                        <tr>
                            <td><?php echo esc_html($vente->kit_id); ?></td>
                            <td><?php echo esc_html($vente->date); ?></td>
                            <td>
                                <?php
                                $delete_url = wp_nonce_url(
                                    admin_url('admin.php?page=compteur-ventes&delete_vente=' . $vente->id),
                                    'delete_vente_' . $vente->id
                                );
                                ?>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-small" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vente ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">Aucune vente enregistrée.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function shortcode_facades() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(id) FROM $this->ventes_table_name");
        return "<span class='facades-label'>Nombre de façades respectées :</span> <span class='facades-count'>{$count}</span>";
    }

    public function shortcode_debit() {
        $this->debit_shortcode_used = true;
        return "<span class='debit-label'>Débit valorisé par les produits Airsam :</span> <span class='debit-valorise-container debit-valorise-loading'>Chargement...</span>";
    }

    public function ajax_get_debit_valorise() {
        check_ajax_referer('debit_valorise_nonce', 'nonce');

        global $wpdb;
        $ventes = $wpdb->get_results("SELECT kit_id, date FROM $this->ventes_table_name");
        $kits = get_option($this->kits_option, []);
        $now = time();
        $total_m3 = 0;

        foreach ($ventes as $vente) {
            $kit_id = $vente->kit_id;
            $date_vente = strtotime($vente->date);
            $heures = max(0, ($now - $date_vente) / 3600);

            if (isset($kits[$kit_id])) {
                $kit = $kits[$kit_id];
                $debit = floatval($kit['debit']);
                $constante_de_division = 4.0;
                if (isset($kit['constante']) && is_numeric($kit['constante']) && floatval($kit['constante']) > 0) {
                    $constante_de_division = floatval($kit['constante']);
                }
                
                $total_m3 += ($heures * $debit) / $constante_de_division;
            }
        }

        if ($total_m3 >= 1_000_000) {
            $val = number_format($total_m3 / 1_000_000, 2, ',', ' ');
            $unit = 'millions de m³';
        } else {
            $val = number_format(round($total_m3), 0, ',', ' ');
            $unit = 'm³';
        }

        wp_send_json_success([
            'val' => $val,
            'unit' => $unit
        ]);
    }

    public function print_debit_script_in_footer() {
        if (!$this->debit_shortcode_used) {
            return;
        }
        
        $nonce = wp_create_nonce('debit_valorise_nonce');
        ?>
        <script type="text/javascript">
            (function($) {
                var initDebitCounter = function() {
                    var containers = $('.debit-valorise-container');

                    // Stop previous intervals to avoid multiple timers in Elementor's editor
                    if (window.debitValoriseInterval) {
                        clearInterval(window.debitValoriseInterval);
                    }

                    if (containers.length > 0 && containers.text() === 'Chargement...') {
                        function updateDebit() {
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: {
                                    action: 'get_debit_valorise',
                                    nonce: '<?php echo $nonce; ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        var data = response.data;
                                        var newText = data.val + ' ' + data.unit;
                                        containers
                                            .removeClass('debit-valorise-loading')
                                            .addClass('debit-valorise-value')
                                            .text(newText);
                                    } else {
                                        containers.text('Erreur de calcul.');
                                    }
                                },
                                error: function() {
                                    containers.text('Erreur de communication.');
                                }
                            });
                        }

                        updateDebit();
                        window.debitValoriseInterval = setInterval(updateDebit, 5000);
                    }
                };

                // Standard page load
                $(document).ready(function() {
                    initDebitCounter();
                });

                // Elementor-specific hook for widgets loaded in editor
                $(window).on('elementor/frontend/init', function() {
                    elementorFrontend.hooks.addAction('frontend/element_ready/widget', function($scope) {
                        initDebitCounter();
                    });
                });

            })(jQuery);
        </script>
        <?php
    }
}

new CompteurDeVentesKits();
