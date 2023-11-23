
let initialItems = []
let loaderTimeoutID = null
const SearchState = {
  query: '',
  field: ''
}

async function init() {
  document.addEventListener("load", () => loaderTimeoutID(true))
  let itemsDataURL = `wp-json/wp/v2/item`
  let movementsDataURL = `wp-json/wp/v2/movement`
  const [itemsResponse, movementsResponse] = await Promise.all([
    fetch(itemsDataURL),
    fetch(movementsDataURL),
  ]);
  let items = await itemsResponse.json()
  initialItems = items.slice()
  let movements = await movementsResponse.json()
  for (let item of initialItems) {
    if (item && item.ACF && item.ACF.movements && item.ACF.movements.length) {
      const itemMovements = item.ACF.movements
      for (let i=0; i<itemMovements.length; i++) {
        const itemMovement = itemMovements[i]
        itemMovements[i] = movements.filter(m => m.id === itemMovement.ID)[0]
      }
    }
  }


  console.log(items);
  buildUI()
  document.querySelector(".search-bar input").addEventListener("input", debounce(handleSearch, 600))
  let selectors = [
    "select#field-select",
    "select#materials-select",
    "select#movement-select",
    '.row.location select[name="room"]',
    '.row.location select[name="position"]',
    '.row.location select[name="shelf"]',
    '.row.location select[name="box"]',
  ]
  for (let selector of selectors) {
    document.querySelector(selector).addEventListener("change", debounce(handleSearch, 600))
  }
}

function buildUI(items) {
  renderInfoTitle(items)
  let resetUI = !items
  if (resetUI) items = initialItems

  buildSearchParams(items)

  buildItemsGrid(items) 
  loaderVisibilty(false)
}

function renderInfoTitle(items) {
  let text
  if (!items) text = initialItems.length + ' פריטים במערכת'
  else text = `נמצאו ${items.length} פריטים במערכת`
  document.querySelector('h2.info-title').innerText = text
}

function buildSearchParams(items) {
  fillSelectWithOptionsFrom(items, 'characteristics', 'field', 'תחום')
  fillSelectWithOptionsFrom(items, 'characteristics', 'materials', 'חומר')
  fillSelectWithOptionsFrom(items, 'location', 'room', 'חדר')
  fillSelectWithOptionsFrom(items, 'location', 'position', 'מיקום')
  fillSelectWithOptionsFrom(items, 'location', 'shelf', 'מדף')
  fillSelectWithOptionsFrom(items, 'location', 'box', 'קופסה')

  let relevantItems = items.filter(i => i && i.ACF && i.ACF.movements)
  let itemMovements = relevantItems.map(item => item.ACF.movements)
  itemMovements = itemMovements.flat()
  let options = itemMovements.map(item => item.ACF.type)
  options = [...new Set(options)]
  
  let selectElm = document.querySelector(`select#movement-select`)
  selectElm.innerHTML = `<option value="">תנועה</option>`

  for (let i = 0; i < options.length; i++) {
    const option = options[i]
    let newOptionElm = document.createElement('option')
    newOptionElm.value = option
    newOptionElm.innerText = option
    if (option == SearchState.movement) newOptionElm.setAttribute('selected', true)
    selectElm.appendChild(newOptionElm)
  }
}

function fillSelectWithOptionsFrom(items, group, attribute, placeholder) {
  let relevantItems = items.filter(i => i && i.ACF && i.ACF[group] && i.ACF[group][attribute])
  let options = relevantItems.map(item => item.ACF[group][attribute])
  options = options.flat()
  options = [...new Set(options)]
  
  let selectElm = document.querySelector(`select#${attribute}-select`)
  selectElm.innerHTML = `<option value="">${placeholder}</option>`

  for (let i = 0; i < options.length; i++) {
    const option = options[i]
    let newOptionElm = document.createElement('option')
    newOptionElm.value = option
    newOptionElm.innerText = option
    if (option == SearchState[attribute]) newOptionElm.setAttribute('selected', true)
    selectElm.appendChild(newOptionElm)
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
}

function handleSearch(e) {
  loaderVisibilty(true)
  let searchTerm = document.querySelector('.search-bar input').value;
  let field = document.querySelector('select#field-select').value;
  let material = document.querySelector('select#materials-select').value;
  let movement = document.querySelector('select#movement-select').value;
  let room = document.querySelector('.row.location select[name="room"]').value;
  let position = document.querySelector('.row.location select[name="position"]').value;
  let shelf = document.querySelector('.row.location select[name="shelf"]').value;
  let box = document.querySelector('.row.location select[name="box"]').value;

  SearchState.query = searchTerm
  SearchState.field = field
  SearchState.material = material
  SearchState.movement = movement
  SearchState.room = room
  SearchState.position = position
  SearchState.shelf = shelf
  SearchState.box = box

  if (!searchTerm && !field && !material && !movement && !room && !position && !shelf && !box) return buildUI()
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
  if (material) {
    resultItems = resultItems.filter(item => item.ACF.characteristics.materials.includes(material))
  }
  if (movement) {
    resultItems = resultItems.filter(item => {
      return !!item.ACF.movements && item.ACF.movements.some(m => m.ACF.type === movement)
    })
  }
  if (room) {
    resultItems = resultItems.filter(item => item.ACF.location.room === room)
  }
  if (position) {
    resultItems = resultItems.filter(item => item.ACF.location.position === position)
  }
  if (shelf) {
    resultItems = resultItems.filter(item => item.ACF.location.shelf === shelf)
  }
  if (box) {
    resultItems = resultItems.filter(item => item.ACF.location.box === box)
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

function resetSearch() {
  document.querySelector('.search-bar input').value = ''
  document.querySelector('select#field-select').value = 'חומר'
  document.querySelector('select#materials-select').value = ''
  document.querySelector('select#movement-select').value = ''
  document.querySelector('.row.location select[name="room"]').value = ''
  document.querySelector('.row.location select[name="position"]').value = ''
  document.querySelector('.row.location select[name="shelf"]').value = ''
  document.querySelector('.row.location select[name="box"]').value = ''

  SearchState.query = ''
  SearchState.field = ''
  SearchState.material = ''
  SearchState.movement = ''
  SearchState.room = ''
  SearchState.position = ''
  SearchState.shelf = ''
  SearchState.box = ''

  buildUI()
}

function loaderVisibilty(on) {
  let loaderElm = document.querySelector('div.loader')
  if (on) loaderElm.classList.remove('hidden')
  else loaderElm.classList.add('hidden')
}

function openAddItem() {
  window.open("/wp-admin/post-new.php?post_type=item", "_blank");
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