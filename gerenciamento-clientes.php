<?php
/*
Plugin Name: Gerenciamento de Clientes
Description: Plugin para cadastrar, editar e excluir clientes e fazer a exibição numa página.
Version: 1.0
Author: Marcio Leite
*/



// Impedindo acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Criação da tabela no banco de dados
function gc_create_client_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'clientes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        celular varchar(15) NOT NULL,
        rua varchar(255) NOT NULL,
        numero varchar(10) NOT NULL,
        bairro varchar(100) NOT NULL,
        cidade varchar(100) NOT NULL,
        estado varchar(100) NOT NULL,
        cep varchar(10) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'gc_create_client_table');

// Adicionando o menu no admin
function gc_clientes_menu() {
    add_menu_page(
        'Gerenciamento de Clientes',
        'Clientes',
        'manage_options',
        'gc-clientes',
        'gc_clientes_page',
        'dashicons-groups',
        20
    );
}
add_action('admin_menu', 'gc_clientes_menu');

// Incluindo o modal
function gc_clientes_modal() {
    ?>
    <style>
        /* Estilo básico do modal */
        .gc-modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .gc-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 80%;
			text-align:right;
        }
        .gc-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .gc-close:hover,
        .gc-close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <script>
        function openModal(clientData) {
            var modal = document.getElementById('gcModal');
            document.getElementById('modalContent').innerHTML = clientData;
            modal.style.display = "block";
        }
        function closeModal() {
            document.getElementById('gcModal').style.display = "none";
        }
        window.onclick = function(event) {
            var modal = document.getElementById('gcModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
    <div id="gcModal" class="gc-modal">
        <div class="gc-modal-content">
            <span class="gc-close" onclick="closeModal()">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>
    <?php
}
add_action('admin_footer', 'gc_clientes_modal');

// Página do plugin no admin
function gc_clientes_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'clientes';

    // Verifica se é para adicionar clientes
    if (isset($_POST['novo_cliente'])) {
        $nomes = $_POST['nome'];
        $emails = $_POST['email'];
        $celulares = $_POST['celular'];
        $ruas = $_POST['rua'];
        $numeros = $_POST['numero'];
        $bairros = $_POST['bairro'];
        $cidades = $_POST['cidade'];
        $estados = $_POST['estado'];
        $ceps = $_POST['cep'];

        foreach ($nomes as $index => $nome) {
            if (!empty($nome)) {
                $wpdb->insert($table_name, array(
                    'nome' => sanitize_text_field($nome),
                    'email' => sanitize_email($emails[$index]),
                    'celular' => sanitize_text_field($celulares[$index]),
                    'rua' => sanitize_text_field($ruas[$index]),
                    'numero' => sanitize_text_field($numeros[$index]),
                    'bairro' => sanitize_text_field($bairros[$index]),
                    'cidade' => sanitize_text_field($cidades[$index]),
                    'estado' => sanitize_text_field($estados[$index]),
                    'cep' => sanitize_text_field($ceps[$index]),
                ));
            }
        }
    }

    // Verifica se é para excluir um cliente
    if (isset($_GET['delete'])) {
        $wpdb->delete($table_name, array('id' => intval($_GET['delete'])));
    }

    // Pesquisa de clientes
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    if (!empty($search)) {
        $clientes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE nome LIKE %s", '%' . $search . '%'));
    } else {
        $clientes = $wpdb->get_results("SELECT * FROM $table_name");
    }

    // Formulário para adicionar múltiplos clientes
    echo '<div class="wrap"><h1>Gerenciamento de Clientes</h1>';
    echo '<form method="post">';
    echo '<h2>Adicionar Novos Clientes</h2>';
    echo '<table class="widefat">';
    echo '<thead><tr><th>Nome</th><th>Email</th><th>Celular</th><th>Rua</th><th>Número</th><th>Bairro</th><th>Cidade</th><th>Estado</th><th>CEP</th></tr></thead><tbody>';
    for ($i = 0; $i < 5; $i++) { // 5 linhas iniciais, mas pode-se adicionar mais
        echo '<tr>';
        echo '<td><input type="text" name="nome[]"></td>';
        echo '<td><input type="email" name="email[]"></td>';
        echo '<td><input type="text" name="celular[]"></td>';
        echo '<td><input type="text" name="rua[]"></td>';
        echo '<td><input type="text" name="numero[]"></td>';
        echo '<td><input type="text" name="bairro[]"></td>';
        echo '<td><input type="text" name="cidade[]"></td>';
        echo '<td><input type="text" name="estado[]"></td>';
        echo '<td><input type="text" name="cep[]"></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<p><input type="submit" name="novo_cliente" value="Adicionar Clientes" class="button-primary"></p>';
    echo '</form>';

    // Formulário de pesquisa
    echo '<h2>Pesquisar Clientes</h2>';
    echo '<form method="post">';
    echo '<input type="text" name="search" placeholder="Buscar por nome..." value="' . esc_attr($search) . '">';
    echo '<input type="submit" value="Pesquisar" class="button">';
    echo '</form>';

    // Lista de clientes
    echo '<h2>Clientes Cadastrados</h2>';
    echo '<table class="widefat">';
    echo '<thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Celular</th><th>Ações</th></tr></thead><tbody>';
    foreach ($clientes as $cliente) {
        $clientData = sprintf(
            '<p><strong>Nome:</strong> %s</p><p><strong>Email:</strong> %s</p><p><strong>Celular:</strong> %s</p><p><strong>Rua:</strong> %s, %s</p><p><strong>Bairro:</strong> %s</p><p><strong>Cidade:</strong> %s - %s</p><p><strong>CEP:</strong> %s</p>',
            esc_html($cliente->nome),
            esc_html($cliente->email),
            esc_html($cliente->celular),
            esc_html($cliente->rua),
            esc_html($cliente->numero),
            esc_html($cliente->bairro),
            esc_html($cliente->cidade),
            esc_html($cliente->estado),
            esc_html($cliente->cep)
        );
        echo '<tr>';
        echo '<td>' . $cliente->id . '</td>';
        echo '<td>' . $cliente->nome . '</td>';
        echo '<td>' . $cliente->email . '</td>';
        echo '<td>' . $cliente->celular . '</td>';
        echo '<td><button type="button" class="button" onclick="openModal(\'' . esc_js($clientData) . '\')">Visualizar</button> <a href="?page=gc-clientes&edit=' . $cliente->id . '" class="button">Editar</a> <a href="?page=gc-clientes&delete=' . $cliente->id . '" class="button button-danger" onclick="return confirm(\'Tem certeza que deseja excluir este cliente?\')">Excluir</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

// Shortcode para exibir a lista de clientes
function gc_clientes_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'clientes';

    $output = '<form method="post"><input type="text" name="search" placeholder="Buscar por nome..."><input type="submit" value="Pesquisar"></form>';

    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = sanitize_text_field($_POST['search']);
        $clientes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE nome LIKE %s", '%' . $search . '%'));
    } else {
        $clientes = $wpdb->get_results("SELECT * FROM $table_name");
    }

    if ($clientes) {
        $output .= '<ul>';
        foreach ($clientes as $cliente) {
            $output .= '<li>' . esc_html($cliente->nome) . ' - ' . esc_html($cliente->email) . ' - ' . esc_html($cliente->celular) . '</li>';
        }
        $output .= '</ul>';
    } else {
        $output .= 'Nenhum cliente encontrado.';
    }

    return $output;
}
add_shortcode('gc_clientes', 'gc_clientes_shortcode');