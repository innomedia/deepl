<% if $UseButtonTag %>
	<button $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>">
		<% if $ButtonContent %>$ButtonContent<% else %><span class="btn__title">$Title.XML</span><% end_if %>
	</button>
<% else %>
	<input $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>"/>
<% end_if %>
<script>
	if(document.getElementById("Form_EditForm_action_doTranslate") != null && document.getElementById("Form_EditForm_action_doTranslate") != undefined)
	{
		document.getElementById("Form_EditForm_action_doTranslate").addEventListener("click",handleClick);
	}
	else if(document.getElementById("Form_ItemEditForm_action_doTranslate") != null && document.getElementById("Form_EditForm_action_doTranslate") != undefined)
	{
		document.getElementById("Form_ItemEditForm_action_doTranslate").addEventListener("click",handleClick);
	}
	
	function handleClick(e)
	{
		if(confirm("Übersetzung starten?") == false)
		{
			e.preventDefault();
		}
	}
</script>