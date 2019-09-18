<?php
class ControllerShippingFreteRapido extends Controller {
    private $error = array();

    public function install() {
        if (version_compare(VERSION, '2.0.0.0', '>')) {
            $this->load->model('extension/event');
            $event = $this->model_extension_event;
        } else {
            $this->load->model('tool/event');
            $event = $this->model_tool_event;
        }

        $this->load->model('localisation/language');
        $this->load->model('localisation/order_status');
        $this->load->language('shipping/freterapido');

        if (version_compare(VERSION, '2.2.0.0', '>=')) {
            $event->addEvent('freterapido_add_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'shipping/freterapido/eventAddOrderHistory');
            $event->addEvent('freterapido_add_order', 'catalog/controller/checkout/confirm/after', 'shipping/freterapido/storeShipping');
        } else {
            $event->addEvent('freterapido_add_order_history', 'post.order.history.add', 'shipping/freterapido/eventAddOrderHistory');
            $event->addEvent('freterapido_add_order', 'post.order.add', 'shipping/freterapido/storeShipping');
        }

        // Insere o status que será usado para a contratação
        $languages = $this->model_localisation_language->getLanguages();
        $new_order_status = array();
        $text_status_awaiting_shipment = $this->language->get('text_status_awaiting_shipment');

        foreach ($languages as $language) {
            $new_order_status['order_status'][$language['language_id']] = array('name' => $text_status_awaiting_shipment);
        }

        $this->model_localisation_order_status->addOrderStatus($new_order_status);

        // Cria a tabela que relaciona as categorias do OpenCart com as do Frete Rápido
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "category_to_fr_category`
            (
                category_id INT(11) NOT NULL,
                fr_category_id INT(11) NOT NULL,
                CONSTRAINT `PRIMARY` PRIMARY KEY (category_id, fr_category_id)
            );
        ");

        // Cria a tabela de categorias do Frete Rápido
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `fr_category`
            (
              fr_category_id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
              name           VARCHAR(255)        NOT NULL,
              code           SMALLINT(6)         NOT NULL
            );
        ");

        // Cria a tabela para inserir metadata dos fretes
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `oc_order_meta`
            (
              meta_id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
              order_id INT(11) NOT NULL,
              meta_key VARCHAR(255),
              meta_value LONGTEXT
            );
        ");

        // Limpa os registros da tabela de categorias
        $this->db->query("
            TRUNCATE TABLE fr_category;
        ");

        // Insere novamente as categorias do Frete Rápido
        $this->db->query("
            INSERT INTO fr_category
            (fr_category_id, name, code) VALUES
            (1, 'Abrasivos', 1),
            (2, 'Acessório para decoração (com vidro)', 109),
            (3, 'Acessório para decoração (sem vidro)', 110),
            (4, 'Acessórios automotivos', 111),
            (5, 'Acessórios de Airsoft / Paintball', 69),
            (6, 'Acessórios de Arquearia', 73),
            (7, 'Acessórios de Pesca', 70),
            (8, 'Acessórios para bicicleta', 112),
            (9, 'Acessórios para celular', 90),
            (10, 'Adubos / Fertilizantes', 2),
            (11, 'Alimentos não perecíveis', 74),
            (12, 'Alimentos perecíveis', 3),
            (13, 'Arquearia', 72),
            (14, 'Artesanatos (com vidro)', 113),
            (15, 'Artesanatos (sem vidro)', 93),
            (16, 'Artigos para Camping', 82),
            (17, 'Artigos para Pesca', 4),
            (18, 'Auto Peças', 5),
            (19, 'Bebidas / Destilados', 6),
            (20, 'Bicicletas (desmontada)', 114),
            (21, 'Bijuteria', 99),
            (22, 'Brindes', 7),
            (23, 'Brinquedos', 8),
            (24, 'CD / DVD / Blu-Ray', 10),
            (25, 'Caixa de embalagem', 75),
            (26, 'Calçados', 9),
            (27, 'Cama / Mesa / Banho', 115),
            (28, 'Cargas refrigeradas/congeladas', 62),
            (29, 'Chapas de madeira', 116),
            (30, 'Cocção Industrial', 102),
            (31, 'Colchão', 66),
            (32, 'Combustíveis / Óleos', 11),
            (33, 'Confecção', 12),
            (34, 'Cosméticos', 13),
            (35, 'Couro', 14),
            (36, 'Derivados Petróleo', 15),
            (37, 'Descartáveis', 16),
            (38, 'Editorial', 17),
            (39, 'Eletrodomésticos', 19),
            (40, 'Eletrônicos', 18),
            (41, 'Embalagens', 20),
            (42, 'Equipamentos de Segurança / API', 88),
            (43, 'Equipamentos de cozinha industrial', 107),
            (44, 'Estiletes / Materiais Cortantes', 84),
            (45, 'Estufa térmica', 106),
            (46, 'Explosivos / Pirotécnicos', 21),
            (47, 'Extintores', 87),
            (48, 'Ferragens', 23),
            (49, 'Ferramentas', 24),
            (50, 'Fibras Ópticas', 25),
            (51, 'Fonográfico', 26),
            (52, 'Fotográfico', 27),
            (53, 'Fraldas / Geriátricas', 28),
            (54, 'Higiene', 29),
            (55, 'Impressos', 30),
            (56, 'Informática / Computadores', 31),
            (57, 'Instrumento Musical', 32),
            (58, 'Joia', 100),
            (59, 'Limpeza', 86),
            (60, 'Linha Branca', 77),
            (61, 'Livro(s)', 33),
            (62, 'Malas / Mochilas', 79),
            (63, 'Manequins)', 117),
            (64, 'Maquina de algodão doce', 104),
            (65, 'Maquina de chocolate', 105),
            (66, 'Materiais Escolares', 34),
            (67, 'Materiais Esportivos', 35),
            (68, 'Materiais Frágeis', 36),
            (69, 'Materiais hidráulicos / Encanamentos', 97),
            (70, 'Material Elétrico / Lâmpada(s)', 39),
            (71, 'Material Gráfico', 40),
            (72, 'Material Hospitalar', 41),
            (73, 'Material Odontológico', 42),
            (74, 'Material Pet Shop', 43),
            (75, 'Material Plástico', 50),
            (76, 'Material Veterinário', 44),
            (77, 'Material de Construção', 37),
            (78, 'Material de Irrigação', 38),
            (79, 'Medicamentos', 22),
            (80, 'Moto Peças', 46),
            (81, 'Mudas / Plantas', 47),
            (82, 'Máquina / Equipamentos', 80),
            (83, 'Móveis com peças de vidro', 68),
            (84, 'Móveis desmontados', 64),
            (85, 'Móveis montados', 45),
            (86, 'Outros', 999),
            (87, 'Papelaria / Documentos', 48),
            (88, 'Papelão', 63),
            (89, 'Perfumaria', 49),
            (90, 'Pia / Vasos', 98),
            (91, 'Pilhas / Baterias', 83),
            (92, 'Pisos (cerâmica) / Revestimentos', 92),
            (93, 'Placa de Energia Solar', 96),
            (94, 'Pneus e Borracharia', 51),
            (95, 'Porta / Janelas (sem vidro)', 95),
            (96, 'Portas / Janelas (com vidro)', 118),
            (97, 'Produto Químico classificado', 85),
            (98, 'Produtos Cerâmicos', 52),
            (99, 'Produtos Químicos Não Classificados', 53),
            (100, 'Produtos Veterinários', 54),
            (101, 'Quadros / Molduras', 94),
            (102, 'Rações / Alimento para Animal', 81),
            (103, 'Refrigeração Industrial', 101),
            (104, 'Revistas', 55),
            (105, 'Sementes', 56),
            (106, 'Simulacro de Arma / Airsoft', 71),
            (107, 'Sofá', 65),
            (108, 'Suprimentos Agrícolas / Rurais', 57),
            (109, 'TV / Monitores', 76),
            (110, 'Tapeçaria / Cortinas / Persianas', 108),
            (111, 'Toldos', 91),
            (112, 'Torneiras', 119),
            (113, 'Travesseiro', 67),
            (114, 'Têxtil', 58),
            (115, 'Utensílios industriais', 103),
            (116, 'Utilidades domésticas', 89),
            (117, 'Vacinas', 59),
            (118, 'Vasos de polietileno', 120),
            (119, 'Vestuário', 60),
            (120, 'Vidros / Frágil', 61),
            (121, 'Vitaminas / Suplementos nutricionais', 78);
        ");

        $row = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA LIKE '" . DB_DATABASE . "' AND TABLE_NAME LIKE '" . DB_PREFIX . "product' AND COLUMN_NAME = 'manufacturing_deadline' ");

        if ($row->num_rows === 0) {
            // Adiciona a coluna 'manufacturing_deadline' na tabela *_product
            $this->db->query("ALTER TABLE " . DB_PREFIX . "product ADD manufacturing_deadline INT(11) DEFAULT '0' NOT NULL AFTER stock_status_id");
        }
    }

    public function index() {
        $this->load->language('shipping/freterapido');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('freterapido', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['text_results_nofilter'] = $this->language->get('text_results_nofilter');
        $data['text_results_cheaper'] = $this->language->get('text_results_cheaper');
        $data['text_results_faster'] = $this->language->get('text_results_faster');

        $data['text_none'] = $this->language->get('text_none');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_select_all'] = $this->language->get('text_select_all');
        $data['text_unselect_all'] = $this->language->get('text_unselect_all');

        $data['entry_freterapido_token'] = $this->language->get('entry_freterapido_token');
        $data['entry_freterapido_token_code'] = $this->language->get('entry_freterapido_token_code');
        $data['entry_cost'] = $this->language->get('entry_cost');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_cnpj']= $this->language->get('entry_cnpj');
        $data['entry_results']= $this->language->get('entry_results');
        $data['entry_limit']= $this->language->get('entry_limit');
        $data['entry_free_shipping']= $this->language->get('entry_free_shipping');
        $data['entry_min_value_free_shipping']= $this->language->get('entry_min_value_free_shipping');
        $data['entry_dimension']= $this->language->get('entry_dimension');
        $data['entry_length']= $this->language->get('entry_length');
        $data['entry_width']= $this->language->get('entry_width');
        $data['entry_height']= $this->language->get('entry_height');

        $data['help_cnpj'] = $this->language->get('help_cnpj');
        $data['help_freterapido_token'] = $this->language->get('help_freterapido_token');
        $data['help_dimension'] = $this->language->get('help_dimension');
        $data['help_dimension_unit'] = $this->language->get('help_dimension_unit');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_general'] = $this->language->get('tab_general');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['cnpj'])) {
            $data['error_cnpj'] = $this->error['cnpj'];
        } else {
            $data['error_cnpj'] = '';
        }

        if (isset($this->error['token'])) {
            $data['error_token'] = $this->error['token'];
        } else {
            $data['error_token'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['action'] = $this->url->link('shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], true);

        if (isset($this->request->post['freterapido_status'])) {
            $data['freterapido_status'] = $this->request->post['freterapido_status'];
        } else {
            $data['freterapido_status'] = $this->config->get('freterapido_status');
        }

        if (isset($this->request->post['freterapido_cnpj'])) {
            $data['freterapido_cnpj'] = $this->request->post['freterapido_cnpj'];
        } else {
            $data['freterapido_cnpj'] = $this->config->get('freterapido_cnpj');
        }

        if (isset($this->request->post['freterapido_results'])) {
            $data['freterapido_results'] = $this->request->post['freterapido_results'];
        } else {
            $data['freterapido_results'] = $this->config->get('freterapido_results');
        }

        if (isset($this->request->post['freterapido_limit'])) {
            $data['freterapido_limit'] = $this->request->post['freterapido_limit'];
        } else {
            $data['freterapido_limit'] = $this->config->get('freterapido_limit');
        }

        if (isset($this->request->post['freterapido_free_shipping'])) {
            $data['freterapido_free_shipping'] = $this->request->post['freterapido_free_shipping'];
        } else {
            $data['freterapido_free_shipping'] = $this->config->get('freterapido_free_shipping');
        }

        if (isset($this->request->post['freterapido_min_value_free_shipping'])) {
            $data['freterapido_min_value_free_shipping'] = $this->request->post['freterapido_min_value_free_shipping'];
        } else {
            $data['freterapido_min_value_free_shipping'] = $this->config->get('freterapido_min_value_free_shipping');
        }

        if (isset($this->request->post['freterapido_msg_prazo'])) {
            $data['freterapido_msg_prazo'] = $this->request->post['freterapido_msg_prazo'];
        } else {
            $data['freterapido_msg_prazo'] = $this->config->get('freterapido_msg_prazo');
        }

        if (isset($this->request->post['freterapido_length'])) {
            $data['freterapido_length'] = $this->request->post['freterapido_length'];
        } else {
            $data['freterapido_length'] = $this->config->get('freterapido_length');
        }

        if (isset($this->request->post['freterapido_width'])) {
            $data['freterapido_width'] = $this->request->post['freterapido_width'];
        } else {
            $data['freterapido_width'] = $this->config->get('freterapido_width');
        }

        if (isset($this->request->post['freterapido_height'])) {
            $data['freterapido_height'] = $this->request->post['freterapido_height'];
        } else {
            $data['freterapido_height'] = $this->config->get('freterapido_height');
        }

        if (isset($this->request->post['freterapido_token'])) {
            $data['freterapido_token'] = $this->request->post['freterapido_token'];
        } else {
            $data['freterapido_token'] = $this->config->get('freterapido_token');
        }

        if (isset($this->request->post['freterapido_sort_order'])) {
            $data['freterapido_sort_order'] = $this->request->post['freterapido_sort_order'];
        } else {
            $data['freterapido_sort_order'] = $this->config->get('freterapido_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        if (version_compare(VERSION, '2.2') < 0) {
            $this->response->setOutput($this->load->view('shipping/freterapido.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('shipping/freterapido', $data));
        }
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'shipping/freterapido')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['freterapido_cnpj']) {
            $this->error['cnpj'] = $this->language->get('error_cnpj');
        }

        if (!$this->request->post['freterapido_token']) {
            $this->error['token'] = $this->language->get('error_token');
        }

        return !$this->error;
    }

    public function uninstall() {
        if (version_compare(VERSION, '2.0.0.0', '>')) {
            $this->load->model('extension/event');
            $event = $this->model_extension_event;
        } else {
            $this->load->model('tool/event');
            $event = $this->model_tool_event;
        }

        $this->load->model('localisation/order_status');
        $this->load->language('shipping/freterapido');

        $event->deleteEvent('freterapido_add_order_history');
        $event->deleteEvent('freterapido_add_order');

        // Exclui o status usado na contratação
        $statuses = $this->model_localisation_order_status->getOrderStatuses();
        $text_status_awaiting_shipment = $this->language->get('text_status_awaiting_shipment');

        $fr_order_status = array_filter($statuses, function ($status) use ($text_status_awaiting_shipment) {
            return $status['name'] == $text_status_awaiting_shipment;
        });

        if (count($fr_order_status) > 0) {
            $this->model_localisation_order_status->deleteOrderStatus(array_pop($fr_order_status)['order_status_id']);
        }
    }
}
