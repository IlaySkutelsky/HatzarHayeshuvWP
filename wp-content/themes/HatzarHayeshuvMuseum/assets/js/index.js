
let initialItems = []
let isLoaderVisible = false
const SearchState = {
  query: '',
  field: ''
}

async function init() {
  let dataURL = `wp-json/wp/v2/item`
  let response = await fetch(dataURL)
  let items = await response.json()
  initialItems = items.slice()
  console.log(items);
  buildUI()
  document.querySelector(".search-bar input").addEventListener("input", debounce(handleSearch, 600))
  document.querySelector("select#field-select").addEventListener("change", debounce(handleSearch, 600))
}

function buildUI(items) {
  renderInfoTitle(items)
  let resetUI = !items
  if (resetUI) items = initialItems

  buildSearchParams(items)

  buildItemsGrid(items) 
}

function renderInfoTitle(items) {
  let text
  if (!items) text = initialItems.length + ' פריטים במערכת'
  else text = `נמצאו ${items.length} פריטים במערכת`
  document.querySelector('h2.info-title').innerText = text
}

function buildSearchParams(items) {
  let relevantItems = items.filter(i => i && i.ACF && i.ACF.characteristics && i.ACF.characteristics.field )
  let fieldOptions = relevantItems.map(item => item.ACF.characteristics.field)
  fieldOptions = [...new Set(fieldOptions)]
  
  let fieldSelectElm = document.querySelector('select#field-select')
  fieldSelectElm.innerHTML = '<option value="">תחום</option>'

  for (let i = 0; i < fieldOptions.length; i++) {
    const option = fieldOptions[i]
    let newOptionElm = document.createElement('option')
    newOptionElm.value = option
    newOptionElm.innerText = option
    // TODO mark as selected if in state
    if (option == SearchState.field) newOptionElm.setAttribute('selected', true)
    fieldSelectElm.appendChild(newOptionElm)
  }
}

function buildItemsGrid(items) {
  let parentElm = document.querySelector('.items-grid')
  parentElm.innerHTML = ''
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    let itemElm = document.createElement('a')
    itemElm.classList.add('item')
    itemElm.href = `/wp-admin/post.php?post=${item.id}&action=edit`
    let itemImageHTMLString
    if (item.fimg_url) itemImageHTMLString = `<div class="featured-image" style="background-image: url('${item.fimg_url}')"></div>`
    // if (item.fimg_url) itemImageHTMLString = `<img class="featured-image" src="${item.fimg_url}"/>`
    else itemImageHTMLString = `<div class="featured-image-placeholder"></div>`
    itemElm.innerHTML = `
      ${itemImageHTMLString}
      <h5>${item.title.rendered}</h5>
      <ul>
        <li class="catalog-number">מספר פריט: ${item.ACF.current_catalog_number}</li>
        <li>${item.ACF.description}</li>
      </ul>
    `
    parentElm.append(itemElm)
  }
  let loaderElm = document.createElement('div')
  loaderElm.classList.add('loader')
  if (!isLoaderVisible) loaderElm.classList.add('hidden')
  parentElm.append(loaderElm)
}

function handleSearch(e) {
  loaderVisibilty(true)
  let searchTerm = document.querySelector('.search-bar input').value;
  let field = document.querySelector('select#field-select').value;
  SearchState.query = searchTerm
  SearchState.field = field
  if (!searchTerm && !field) return buildUI()
  let resultItems = structuredClone(initialItems)
  if (searchTerm) {
    resultItems = resultItems.reduce((prevValue, curValue) => {
      let resultItem = getResultItemFromQuery(curValue, searchTerm)
      if (resultItem) prevValue.push(resultItem)
      return prevValue
    }, [])
  }
  if (field) {
    resultItems = resultItems.filter(item => item.ACF.characteristics.field == field)
  }
  buildUI(resultItems)
  loaderVisibilty(false)
}

const excludedKeys = [
  'fimg_url'
]

function getResultItemFromQuery(item, searchTerm) {
  let foundSearchTerm = false;
  for (let key in item) {
    if (typeof item[key] === 'object' && item[key] !== null) {
      let resultSubItem = getResultItemFromQuery(item[key], searchTerm)
      if (resultSubItem) {
        foundSearchTerm = true
        item[key] = resultSubItem
      }
    } else if (item.hasOwnProperty(key)) {
      let value = item[key]
      if (excludedKeys.includes(key)) continue
      if (typeof value==='string' && value.match(searchTerm)) {
        item[key] = value.replaceAll(searchTerm, function(a, b){
            return '<i>' + a + '</i>';
        })
        foundSearchTerm = true
      }
    }
  }
  if (foundSearchTerm) return item
  else return null
}

function loaderVisibilty(on) {
  let loaderElm = document.querySelector('.items-grid .loader')
  if (on) loaderElm.classList.remove('hidden')
  else loaderElm.classList.add('hidden')
}

const debounce = (callback, wait) => {
  let timeoutId = null;
  return (...args) => {
    window.clearTimeout(timeoutId);
    timeoutId = window.setTimeout(() => {
      callback(...args);
    }, wait);
  };
}

init()