
let exhibitions, items
let loaderTimeoutID = null

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
  let itemsDataURL = `/wp-json/wp/v2/item`
  let movementsDataURL = `/wp-json/wp/v2/movement`
  let exhibitionsDataURL = `/wp-json/wp/v2/exhibition`
  const [itemsResponse, movementsResponse, exhibitionsResponse] = await Promise.all([
    getAllofType(itemsDataURL),
    getAllofType(movementsDataURL),
    getAllofType(exhibitionsDataURL),
  ]);
  exhibitions = await exhibitionsResponse.json()
  items = await itemsResponse.json()
  // for (let item of initialItems) {
  //   if (item && item.ACF && item.ACF.movements && item.ACF.movements.length) {
  //     const itemMovements = item.ACF.movements
  //     for (let i=0; i<itemMovements.length; i++) {
  //       const itemMovement = itemMovements[i]
  //       itemMovements[i] = movements.filter(m => m.id === itemMovement.ID)[0]
  //     }
  //   }
  // }

  console.log(exhibitions);
  buildUI()
}

function buildUI(items) {
  renderInfoTitle(items)

  buildItemsGrid(items) 
  loaderVisibilty(false)
}

function renderInfoTitle(items) {
  document.querySelector('h2.info-title').innerText = `${exhibitions.length} תערוכות במערכת`
}

function buildItemsGrid(items) {
  let parentElm = document.querySelector('.items-grid')
  parentElm.innerHTML = ''
  for (let i = 0; i < exhibitions.length; i++) {
    const exhibition = exhibitions[i];
    let exhibitionElm = document.createElement('a')
    exhibitionElm.classList.add('item')
    // exhibitionElm.href = exhibitionElm.link
    let itemImageHTMLString
    if (exhibition.ACF.image) itemImageHTMLString = `<div class="featured-image" style="background-image: url('${exhibition.ACF.image}')"></div>`
    else itemImageHTMLString = `<div class="featured-image-placeholder"></div>`
    exhibitionElm.innerHTML = `
      ${itemImageHTMLString}
      <h5>${exhibition.title.rendered}</h5>
      <ul>
        <li class="catalog-number">${exhibition.ACF.description_hebrew}</li>
        <li class="catalog-number">פריטים:
          <ul>
              ${listOfItemsInExhibition(exhibition)}
          </ul>
        </li>
      </ul>
    `
    parentElm.append(exhibitionElm)
  }
}

function listOfItemsInExhibition(exhibition) {
  let str = ''
  for (let i = 0; i < exhibition.ACF.exhibited_items.length; i++) {
    let item = exhibition.ACF.exhibited_items[i]
    str += `
      <li style="list-style:unset">
        <a href="${linkFromItemID(item.ID)}">
          ${item.post_title} - ${item.ID}
        </a>
      </li>` 
  }
  return str
}

function linkFromItemID(id) {
  return items.find(i => i.id == id).link
}

function loaderVisibilty(on) {
  let loaderElm = document.querySelector('div.loader')
  if (on) loaderElm.classList.remove('hidden')
  else loaderElm.classList.add('hidden')
}

function openAddExhibition() {
  window.open("/wp-admin/post-new.php?post_type=exhibition", "_blank");
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