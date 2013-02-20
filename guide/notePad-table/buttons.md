#Buttons

If you turn on this feature you'll get an extra column at the end of every row that contains an edit and delete button.

They both get a data-id property containing the row's primary key value. Next to that they get a special class assigned prefixed by btn-action-(edit|remove). Making it easy to add an event handler on the dataTable that delegates a click event to any button it finds with this class.

## Capturing a button click
The table normally already gets initialised by the js file that's generated by grids, let's create a new js file that handles the click events.

The way we capture these click events are possible starting jQuery 1.7
```js
$(document).ready(function(){
	//capturing the edit button's click
	$("table#users tbody").on("click", ".btn-action-edit", function(e){
		e.preventDefault();
		
		var rowId = $(this).data("id");
		//do whatever you want now you have the ID
	});
});
```

## Managing row buttons

By default buttons don't get added to each row, you can toggle support by turning it on like this;
```php
$dataTable = new Table;
$dataTable->show_buttons(true);
```

By default an edit and delete button get added, if you don't need them you can remove them like this:
```php
//remove the edit button
$dataTable->remove_button('edit');

//remove the delete button
$dataTable->remove_button('remove');
```

That's nice and all but I'm sure you'd like to know how you can add your own button.
The first argument you pass to the add_button method will be the identifier (gets prefixed with *btn-action-*) in your HTML,
the second parameter defines the icon that will be displayed in the button (it gets prefixed with *icon-*).

All the other parameters are optionial; You can define the button's class (get's prefixed with *btn-*), choose where to where to place the button (at the *start* or *end* of the list, or even *before* or *after*), if the previous parameter has *before* or *after* as a value this next parameter will hold the name of the button you'll locate this one relative to.
```php
//add a tokens buttons
$dataTable->add_button('tokens', 'tags');

//add it with a different button class
$dataTable->add_button('tokens', 'tags', 'primary');

//add it after the edit button
$dataTable->add_button('tokens', 'tags', null, 'after', 'edit');

//or before the remove button
$dataTable->add_button('tokens', 'tags', null, 'before', 'remove');
```

At any time you can still move around your existing buttons by calling *button_move*:
```php
$dataTable->move_button('tokens', 'after', 'edit');
```