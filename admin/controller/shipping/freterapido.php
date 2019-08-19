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
            (2, 'Acessórios de Airsoft / Paintball', 69),
            (3, 'Acessórios de Arquearia', 73),
            (4, 'Acessórios de Pesca', 70),
            (5, 'Acessórios para celular', 90),
            (6, 'Adubos / Fertilizantes', 2),
            (7, 'Alimentos não perecíveis', 74),
            (8, 'Alimentos perecíveis', 3),
            (9, 'Arquearia', 72),
            (10, 'Artesanatos', 93),
            (11, 'Artigos para Camping', 82),
            (12, 'Artigos para Pesca', 4),
            (13, 'Auto Peças', 5),
            (14, 'Bebidas / Destilados', 6),
            (15, 'Bijuteria', 99),
            (16, 'Brindes', 7),
            (17, 'Brinquedos', 8),
            (18, 'Caixa de embalagem', 75),
            (19, 'Calçados', 9),
            (20, 'Cargas refrigeradas/congeladas', 62),
            (21, 'CD / DVD / Blu-Ray', 10),
            (22, 'Cocção Industrial', 102),
            (23, 'Colchão', 66),
            (24, 'Combustíveis / Óleos', 11),
            (25, 'Confecção', 12),
            (26, 'Cosméticos', 13),
            (27, 'Couro', 14),
            (28, 'Derivados Petróleo', 15),
            (29, 'Descartáveis', 16),
            (30, 'Editorial', 17),
            (31, 'Eletrodomésticos', 19),
            (32, 'Eletrônicos', 18),
            (33, 'Embalagens', 20),
            (34, 'Equipamentos de cozinha industrial', 107),
            (35, 'Equipamentos de Segurança / API', 88),
            (36, 'Estiletes / Materiais Cortantes', 84),
            (37, 'Estufa térmica', 106),
            (38, 'Explosivos / Pirotécnicos', 21),
            (39, 'Extintores', 87),
            (40, 'Ferragens', 23),
            (41, 'Ferramentas', 24),
            (42, 'Fibras Ópticas', 25),
            (43, 'Fonográfico', 26),
            (44, 'Fotográfico', 27),
            (45, 'Fraldas / Geriátricas', 28),
            (46, 'Higiene', 29),
            (47, 'Impressos', 30),
            (48, 'Informática / Computadores', 31),
            (49, 'Instrumento Musical', 32),
            (50, 'Joia', 100),
            (51, 'Limpeza', 86),
            (52, 'Linha Branca', 77),
            (53, 'Livro(s)', 33),
            (54, 'Malas / Mochilas', 79),
            (55, 'Maquina de algodão doce', 104),
            (56, 'Maquina de chocolate', 105),
            (57, 'Materiais Escolares', 34),
            (58, 'Materiais Esportivos', 35),
            (59, 'Materiais Frágeis', 36),
            (60, 'Materiais hidráulicos', 97),
            (61, 'Material de Construção', 37),
            (62, 'Material de Irrigação', 38),
            (63, 'Material Elétrico / Lâmpada(s)', 39),
            (64, 'Material Gráfico', 40),
            (65, 'Material Hospitalar', 41),
            (66, 'Material Odontológico', 42),
            (67, 'Material Pet Shop', 43),
            (68, 'Material Plástico', 50),
            (69, 'Material Veterinário', 44),
            (70, 'Medicamentos', 22),
            (71, 'Moto Peças', 46),
            (72, 'Mudas / Plantas', 47),
            (73, 'Máquina / Equipamentos', 80),
            (74, 'Móveis com peças de vidro', 68),
            (75, 'Móveis desmontados', 64),
            (76, 'Móveis montados', 45),
            (77, 'Outros', 999),
            (78, 'Papelaria / Documentos', 48),
            (79, 'Papelão', 63),
            (80, 'Perfumaria', 49),
            (81, 'Pia / Vasos', 98),
            (82, 'Pilhas / Baterias', 83),
            (83, 'Pisos (cerâmica) / Revestimentos', 92),
            (84, 'Placa de Energia Solar', 96),
            (85, 'Pneus e Borracharia', 51),
            (86, 'Porta / Janelas', 95),
            (87, 'Produto Químico classificado', 85),
            (88, 'Produtos Cerâmicos', 52),
            (89, 'Produtos Químicos Não Classificados', 53),
            (90, 'Produtos Veterinários', 54),
            (91, 'Quadros / Molduras', 94),
            (92, 'Rações / Alimento para Animal', 81),
            (93, 'Refrigeração Industrial', 101),
            (94, 'Revistas', 55),
            (95, 'Sementes', 56),
            (96, 'Simulacro de Arma / Airsoft', 71),
            (97, 'Sofá', 65),
            (98, 'Suprimentos Agrícolas / Rurais', 57),
            (99, 'Tapeçaria / Cortinas / Persianas', 108),
            (100, 'Toldos', 91),
            (101, 'Travesseiro', 67),
            (102, 'TV / Monitores', 76),
            (103, 'Têxtil', 58),
            (104, 'Utensílios industriais', 103),
            (105, 'Utilidades domésticas', 89),
            (106, 'Vacinas', 59),
            (107, 'Vestuário', 60),
            (108, 'Vidros / Frágil', 61),
            (109, 'Vitaminas / Suplementos nutricionais', 7);
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
