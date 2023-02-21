// to remove the specific key
function removeUrlParameter(paramKey) {
  var url = window.location.href
  var r = new URL(url)
  r.searchParams.delete(paramKey)
  var newUrl = r.href
  window.history.pushState({ path: newUrl }, '', newUrl)
}