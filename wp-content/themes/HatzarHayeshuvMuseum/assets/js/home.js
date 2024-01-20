
let initialItems = []
let loaderTimeoutID = null
const SearchState = {
  query: '',
  field: ''
}

async function recursiveFetchType(url, counter) {
  if (!counter) counter=1
  let items = await fetch(url+'?per_page=100&page='+counter)
  if (items.length >= counter*100) items.push(...recursiveFetchType(url, counter++))
  return items
}

function getAllofType(url) {
  return new Promise(async function(resolve, reject) {
    try {
      let allItems = recursiveFetchType(url)
      if (allItems) resolve(allItems)
      else reject('no items from url ' + url)
    } catch (error) {
      reject(error)
    }
  })
}

async function init() {
  document.addEventListener("load", () => loaderTimeoutID(true))
  let itemsDataURL = `wp-json/wp/v2/item`
  let movementsDataURL = `wp-json/wp/v2/movement`
  const [itemsResponse, movementsResponse] = await Promise.all([
    getAllofType(itemsDataURL),
    getAllofType(movementsDataURL),
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
    document.querySelector(selector).addEventListener("change", handleSearch)
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
  options = options.map(i => i.replace(/(<i>|<\/i>)/g, ''))
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
  options = options.map(i => typeof(i)==='object'?Object.values(i):i)
  options = options.flat()
  options = options.map(i => i.replace(/(<i>|<\/i>)/g, ''))
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
    itemElm.href = item.link
    let itemImageHTMLString
    if (item.fimg_url) itemImageHTMLString = `<div class="featured-image" style="background-image: url('${item.fimg_url}')"></div>`
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
  try {
    let searchTerm = document.querySelector('.search-bar input').value;
    let field = document.querySelector('select#field-select').value;
    let materials = document.querySelector('select#materials-select').value;
    let movement = document.querySelector('select#movement-select').value;
    let room = document.querySelector('.row.location select[name="room"]').value;
    let position = document.querySelector('.row.location select[name="position"]').value;
    let shelf = document.querySelector('.row.location select[name="shelf"]').value;
    let box = document.querySelector('.row.location select[name="box"]').value;

    SearchState.query = searchTerm
    SearchState.field = field
    SearchState.materials = materials
    SearchState.movement = movement
    SearchState.room = room
    SearchState.position = position
    SearchState.shelf = shelf
    SearchState.box = box

    if (!searchTerm && !field && !materials && !movement && !room && !position && !shelf && !box) return buildUI()
    let resultItems = structuredClone(initialItems)
    if (searchTerm) {
      resultItems = resultItems.reduce((prevValue, curValue) => {
        let resultItem = getResultItemFromQuery(curValue, searchableProperties, searchTerm)
        if (resultItem) prevValue.push(resultItem)
        return prevValue
      }, [])
    }
    if (field) {
      resultItems = resultItems.filter(item => item.ACF.characteristics.field == field)
    }
    if (materials) {
      resultItems = resultItems.filter(function(item) {
        if (typeof(item.ACF.characteristics.materials) === 'object') item.ACF.characteristics.materials = Object.values(item.ACF.characteristics.materials)
        return item.ACF.characteristics.materials.includes(materials)
      })
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
  } catch (error) {
    loaderVisibilty(false)
  }
}

const excludedKeys = [
  'fimg_url'
]

const searchableProperties = {
  title: {
    rendered: true
  },
  ACF: {
    attribution: true,
    condition: {
      physical_description: true
    },
    current_catalog_number: true,
    description: true,
    hebrew_date: true,
    history: true,
    minting: true,
    notes: true,
    origin: true,
    photographer: true,
    previous_catalog_number: true,
    source: true
  }
}

function getResultItemFromQuery(item, searchableProperties, searchTerm) {
  let foundSearchTerm = false;
  for (let key in item) {
    if (!searchableProperties[key]) continue
    if (typeof item[key] === 'object' && item[key] !== null) {
      let resultSubItem = getResultItemFromQuery(item[key], searchableProperties[key], searchTerm)
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
  SearchState.materials = ''
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

function toPlainText(text) {
    let areaElement = document.createElement("textarea");
    areaElement.innerHTML = text;

    return areaElement.value;
}

init()