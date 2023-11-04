
let initialItems = []

async function init() {
  let dataURL = `wp-json/wp/v2/item`
  let response = await fetch(dataURL)
  let items = await response.json()
  initialItems = items.slice()
  console.log(items);
  buildItemsGrid(items)
  document.querySelector(".search-bar input").addEventListener("input", debounce(handleSearchBarInput, 1000))
}

function clearItemsGrid() {
  let parentElm = document.querySelector('.items-grid')
  parentElm.innerHTML = ''
}

function buildItemsGrid(items) {
  let parentElm = document.querySelector('.items-grid')
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    let itemElm = document.createElement('a')
    itemElm.classList.add('item')
    itemElm.href = `/wp-admin/post.php?post=${item.id}&action=edit`
    itemElm.innerHTML = `
      <h5>${item.title.rendered}</h5>
      <ul>
        <li class="catalog-number">מספר פריט: ${item.ACF.current_catalog_number}</li>
        <li>${item.ACF.description}</li>
      </ul>
    `
    parentElm.append(itemElm)
  }
}

function handleSearchBarInput(e) {
  let searchTerm = e.target.value;
  if (!searchTerm) return
  let resultItems = structuredClone(initialItems).reduce((prevValue, curValue) => {
    let resultItem = getResultItem(curValue, searchTerm)
    if (resultItem) prevValue.push(resultItem)
    return prevValue
  }, [])
  clearItemsGrid()
  buildItemsGrid(resultItems)
}

function getResultItem(item, searchTerm) {
  let foundSearchTerm = false;
  for (let key in item) {
    if (typeof item[key] === 'object' && item[key] !== null) {
      let resultSubItem = getResultItem(item[key], searchTerm)
      if (resultSubItem) {
        foundSearchTerm = true
        item[key] = resultSubItem
      }
    } else if (item.hasOwnProperty(key)) {
      let value = item[key]
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