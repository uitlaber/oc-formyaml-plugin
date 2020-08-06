# oc-formyaml-plugin
Form generator from yaml file

# How to use
In the plugin folder, create a forms folder and add your forms there. For example position_add.yaml, comment_add.yaml, etc.
Example yaml file:
```
fields:
  url:
    label: Link
    required: true
    wrapper: col-md-12
  price_yuan:
    label: 'Price ¥'
    type: number
    required: true
    wrapper: col-md-6
    attributes:
      step: 0.01
  quantity:
    label: 'Quantity'
    type: number
    required: true
    wrapper: col-md-6
  delivery_price_yuan:
    label: 'China Shipping ¥'
    type: number
    required: true
    wrapper: col-md-12
    attributes:
      step: 0.01
  photo_report:
    label: 'Order photo report {photo_report}'
    type: checkbox
    wrapper: col-md-6
  order_measurement:
    label: 'Order measurement {order_measurement}'
    type: checkbox
    wrapper: col-md-6
  pack_bubble:
    label: 'Pack in bubble wrap {pack_bubble}'
    type: checkbox
    wrapper: col-md-12
  other_service_check:
    label: 'Add. service'
    type: checkbox
    wrapper: col-md-12
  other_service:
    label: ''
    placeholder: 'Add. service'
    type: textarea
    wrapper: col-md-12
  comment:
    label: 'Comment'
    placeholder: 'Comment'
    type: textarea
    wrapper: col-md-12
  photos:
    label: 'Attach product photo'
    type: file
    multiple: true
    mode: image
    hidefilename: true
    preview: true
    wrapper: col-md-12

success: $ ('. modal'). modal ('hide'); this.reset (); $ (this) .find ('. is-invalid'). removeClass ('is-invalid'); $ (this) .find ('. formyaml-upload-container'). html (''); $ ('[name = \' add-position [other_service] \ ']'). hide ()
buttons:
  button:
    text: Create
    type: submit
    attributes:
      class: btn btn-primary btn-block
      data-attach-loading: ''

```
List of field types at the moment
- text (Default)
- checkbox
- date-range
- email
- file
- number
- select
- textarea
- radio

Form output on the site
```
{{makeForm ('formName') | raw}}
```


Let's add data to the form, for example
```
{{m akeForm ('formName', ', {id: formData.id}, formData) | raw}}
```
