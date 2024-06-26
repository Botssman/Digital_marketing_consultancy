<?php

namespace Amristar\WTO\Components;

use Cms\Classes\ComponentBase;
use Mail;
use Tailor\Models\EntryRecord;
use Tailor\Models\GlobalRecord;

class Forms extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Forms',
            'description' => 'Forms support for WTO',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        // $this->addCss('/plugins/amristar/wto/assets/css/frontedit.css');
        // $this->addJs('/plugins/amristar/wto/assets/js/frontedit.js');

        $this->forms = json_encode($this->loadForms());
    }

    public function loadForms()
    {
        $forms = GlobalRecord::findForGlobal('wto_forms');
        $forms->load('forms');

        $forms_data = [];
        $frontend_fields = ['selector', 'hide_form', 'hide_lbox', 'lbox_selector', 'redirect', 'hide_delay', 'new_window', 'success_message', 'error_message'];
        $form_id = 0;
        $forms_data = [];
        foreach ($forms->forms as $form) {
            foreach ($frontend_fields as $field) {
                $form_data[$field] = $form[$field];
            }
            $form_data['form_id'] = $form_id;
            $forms_data[] = $form_data;
            $form_id += 1;
        }
        return $forms_data;
    }

    public $forms;

    public function onFormSend()
    {
        $forms = GlobalRecord::findForGlobal('wto_forms');
        $forms->load('forms');
        $form = $forms->forms[post('wto_form_id')];

        $vars = [];
        $fields = [];
        foreach (post() as $key => $value) {

            $name = str_replace('_', ' ', $key);

            if ($value === 'on') $value = 'Да';

            if ($key != 'wto_form_id') {
                $fields[] = ['name' => $name, 'value' => $value];
            }
        }

        $vars['fields'] = $fields;

        Mail::send($form['template'], $vars, function ($message) use ($form) {

            $message->to($form['email_to']);

            $param = $form['reply_to'];
            if (!empty($param)) {
                $message->replyTo($param);
            }

            $param = $form['copy_cc'];
            if (!empty($param)) {
                $message->cc($param);
            }

            $param = $form['copy_bcc'];
            if (!empty($param)) {
                $message->bcc($param);
            }
        });

        return (['status' => 'success']);
    }

    public function onCreateOrder()
    {
        $config_shop = GlobalRecord::findForGlobal('shop_config');
        $post = EntryRecord::inSection('shop_order');

        $post->title = date('Y-m-d H:m:s') . ' | ' . post('name') . ' | ' . post('email') . ' | ' . post('phone');
        $post->status = 'Новый';
        $post->client_name = post('name');
        $post->client_email = post('email');
        $post->client_phone = post('phone');
        $post->client_address = post('address');
        $post->content = post('content');
        $post->total = post('total');
        $post->politic_accept = post('politic');

        $post->save();

        $fields = [
            'name' => post('name'),
            'phone' => post('phone'),
            'email' => post('email'),
            'address' => post('address'),
            'content' => post('content'),
            'total' => post('total'),
            'politic' => post('politic'),
        ];

        if (!empty($config_shop->order_email)) {
            Mail::send('shop.order', $fields, function ($message) use ($config_shop) {
                $message->to($config_shop->order_email);
            });
        }

        return (['status' => 'success']);
    }
}
