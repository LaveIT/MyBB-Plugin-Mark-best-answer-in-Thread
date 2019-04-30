You can use the following variables in postbit, if you have activated the plugin.
(Templates & Styles -> Templates -> your Theme -> Post Bit Templates -> postbit)

Show all "best answers" a user has received = {$post['countsba']}
Show "Mark as best answer"-button = {$post['button_ba']}
Show the "Delete"-button = {$post['button_del_ba']}

<div class> for the background-color-change: {$post['ts_class']}

Important (!!!): 
This plugin was written for the default theme of MyBB. 
The variables in the standard theme are automatically set in the postbit template.
You may have to set the variables manually in your individual template!