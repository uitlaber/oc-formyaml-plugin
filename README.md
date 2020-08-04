# oc-formyaml-plugin
Генератор форм из yaml файла

# Как использвать
В папке плагина создаете папку forms и туда добавляете свои формы. Например position_add.yaml,  comment_add.yaml итд.
Пример yaml файла:
```
fields:
  url:
    label: Ссылка
    required: true
    wrapper: col-md-12
  price_yuan:
    label: 'Цена ¥'
    type: number
    required: true
    wrapper: col-md-6
    attributes:
      step: 0.01
  quantity:
    label: 'Количество'
    type: number
    required: true   
    wrapper: col-md-6
  delivery_price_yuan:
    label: 'Доставка по Китаю ¥'
    type: number
    required: true 
    wrapper: col-md-12
    attributes:
      step: 0.01
  photo_report:
    label: 'Заказать фотоотчет {photo_report}'
    type: checkbox
    wrapper: col-md-6    
  order_measurement:
    label: 'Заказать замер {order_measurement}'
    type: checkbox
    wrapper: col-md-6    
  pack_bubble:
    label: 'Упаковать в пузырчатую пленку {pack_bubble}'
    type: checkbox
    wrapper: col-md-12   
  other_service_check:
    label: 'Доп. услуга'
    type: checkbox
    wrapper: col-md-12     
  other_service:
    label: ''
    placeholder: 'Доп. услуга'
    type: textarea
    wrapper: col-md-12   
  comment:
    label: 'Комментарий'
    placeholder: 'Комментарий'
    type: textarea
    wrapper: col-md-12
  photos:
    label: 'Прикрепить фото товара'
    type: file
    multiple: true
    mode: image
    hidefilename: true
    preview: true
    wrapper: col-md-12

success: $('.modal').modal('hide'); this.reset(); $(this).find('.is-invalid').removeClass('is-invalid'); $(this).find('.formyaml-upload-container').html('');$('[name=\'add-position[other_service]\']').hide()
buttons:
  button:
    text: Создать
    type: submit
    attributes:
      class: btn btn-primary btn-block
      data-attach-loading: ''

```
Список типов полей на данный момент
- text (По умолчанию)
- checkbox
- date-range
- email
- file
- number
- select
- textarea


Вывод формы на сайте 
```
{{ makeForm('uit/cargo','add-position','onAddPosition')|raw }}
```
Добавим данные к форме например хотим создать форму для редактирования позиции.
```
{{ makeForm('uit/cargo','update-position','onUpdatePosition',{id:formData.id},formData)|raw}}
``` 
 
- uit/cargo это название моего плагина в котором лежит папка forms 
- add-position это имя файла yaml
- onAddPosition это обработчик (data-request). Вы должны вручную создать обработчик формы в вашем компоненте.
- {id:formData.id},formData данные формы

### Класы для полей можно добавить в настройках в админке 

Пример моего компонента:
```
<?php namespace Uit\Cargo\Components;

use Cms\Classes\ComponentBase;
use Uit\Cargo\Classes\BaseComponentTrait;
use Uit\Cargo\Models\Order as OrderModel;
use Uit\Cargo\Models\OrderItem;
use RainLab\User\Facades\Auth;
use System\Models\File;
use Uit\Cargo\Models\OrderQuestion;
use Validator;
use ValidationException;
use Flash;
use Event;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class Order extends ComponentBase
{
    use BaseComponentTrait;

    public $order;

    public function componentDetails()
    {
        return [
            'name'        => 'Order Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->page['order'] = $this->order = $this->loadOrder();
        $user = Auth::getUser();
        if(is_null($this->order) || $this->order->user_id != $user->id   ) return $this->pageNotFound();

        if(get('export')){
            return $this->export();
        }
        
    }

    public function loadOrder() 
    {
        return OrderModel::find($this->param('id'));
    }  

    public function onOpenPosition(){
            
        $item = OrderItem::find(post('id'));
        $formData = $item->toArray();
        if(!is_null($item->other_service) && !empty(trim($item->other_service))){
            $formData['other_service_check'] = true;            
        }
        if($item->photos->count()){
            $formData['photos'] = $item->photos;
        }
        return [
            '#edit-position-form' => $this->renderPartial('@_edit-position-form', compact('formData'))
        ];

    }

    public function onAddPosition(){

        $order = $this->loadOrder();
        $user = Auth::getUser();

        if (is_null($user)
            || $order->user_id != $user->id
            || !$order->is_editable
        ) {
            Flash::error('Не возможно добавить позицию');
            return;
        }

        $data = post('add-position');
        $rules = [
            'url' => 'required|url',
            'price_yuan' => 'required|numeric',
            'quantity' => 'required|numeric',
            'delivery_price_yuan' => 'required|numeric',
        ];

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $orderItem = new OrderItem;
        $orderItem->url = $data['url'];
        $orderItem->price_yuan = $data['price_yuan'];
        $orderItem->quantity = $data['quantity'];
        $orderItem->delivery_price_yuan = $data['delivery_price_yuan'];
        if(isset($data['photo_report']))
            $orderItem->photo_report = true;
        if (isset($data['photo_report']))
            $orderItem->order_measurement = true;
        if (isset($data['pack_bubble']))
            $orderItem->pack_bubble = true;
        $orderItem->other_service = $data['other_service'];
        $orderItem->comment = $data['comment'];
        $orderItem->status_id = 1;
        $orderItem->order_id = $order->id;
        $orderItem->save();

        if (isset($data['photos'])) {
            foreach ($data['photos'] as $file_id) {
                $file = File::find($file_id);
                if ($file) $orderItem->photos()->save($file);
            }
        }
        
        Flash::success('Позиция добавлено');
        return $this->reloadOrderInfo($order);
    }

    public function onUpdatePosition()
    {
        $order = $this->loadOrder();
        $data = post('update-position');
        $orderItem = $order->items()->where('id', $data['id'])->first();
        $user = Auth::getUser();
        if (is_null($user)
            || $order->user_id != $user->id
            || !$order->is_editable
            || is_null($orderItem)
            || !$orderItem->is_editable
            
            ) {
            Flash::error('Не возможно изменить информацию');
            return;
        }

        $orderItem->url = $data['url'];
        $orderItem->price_yuan = $data['price_yuan'];
        $orderItem->quantity = $data['quantity'];
        $orderItem->delivery_price_yuan = $data['delivery_price_yuan'];
        if (isset($data['photo_report']))
            $orderItem->photo_report = true;
        if (isset($data['photo_report']))
            $orderItem->order_measurement = true;
        if (isset($data['pack_bubble']))
            $orderItem->pack_bubble = true;
        $orderItem->other_service = $data['other_service'];
        $orderItem->comment = $data['comment'];
        //@TODO статус 1 Черновик
        $orderItem->save();


        if (isset($data['photos'])) {
            foreach ($data['photos'] as $file_id) {
                $file = File::find($file_id);
                if ($file) $orderItem->photos()->save($file);
            }            
        }
        Flash::success('Позиция обнавлена');
        return $this->reloadOrderInfo($order);


    }

    public function reloadOrderInfo($order){
        return [
            '#order-items-container' => $this->renderPartial('@_order-items', compact('order')),
            '#order-finance-container' => $this->renderPartial('@_order-finance', compact('order')),
            '#order-total-price' => $this->renderPartial('@_total-price', compact('order')),
        ];
    }

    
    public function onSaveOrder()
    {
        Event::fire('uit.cargo::order.created', $this->loadOrder());
    }
        
}

```


 


