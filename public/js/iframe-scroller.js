window.addEventListener("message", recieveMessage, false);

var element;

function recieveMessage(event)
{
  var data = event.data;
  if (data.action == 'scrollTo')
  {
    if (element != null)
    {
      element.classList.remove('active_hit');
    }
    element = document.getElementById(data.element);
    element.classList.add('active_hit');
    element.scrollIntoView();
  }
}
