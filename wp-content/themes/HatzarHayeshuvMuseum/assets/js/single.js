

async function recursiveFetchType(url, counter) {
  if (!counter) counter=1
  let items = await fetch(url+'?per_page=100&page='+counter)
  .then(res => res.json())
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

function getMovementByID(id) {
  return window.WP_MOVEMENTS.find(m => m.id === id)
}

async function init() {

  let itemsDataURL = `/wp-json/wp/v2/item`
  let movementsDataURL = `/wp-json/wp/v2/movement`
  const [itemsResponse, movementsResponse] = await Promise.all([
    getAllofType(itemsDataURL),
    getAllofType(movementsDataURL),
  ]);
  window.WP_MOVEMENTS = movementsResponse
  window.WP_ITEMS = itemsResponse

  console.log(window.WP_POST_TYPE);
  console.log(window.WP_POST);
  console.log(window.WP_POST_ACF);
  console.log(window.WP_MOVEMENTS);
  console.log(window.WP_ITEMS);

  let post = window.WP_POST
  let acf = window.WP_POST_ACF

  document.getElementById('editing-link').href = `/wp-admin/post.php?post=${post.ID}&action=edit`

  document.getElementById('featured-image').src = window.WP_POST_IMAGE
  document.getElementById('title').innerHTML = post.post_title
  document.getElementById('desc').innerHTML = acf.description

  document.getElementById('current-catalog-number').innerHTML = acf.current_catalog_number
  document.getElementById('previous-catalog-number').innerHTML = acf.previous_catalog_number
  document.getElementById('date').innerHTML = acf.date
  document.getElementById('hebrew-date').innerHTML = acf.hebrew_date
  document.getElementById('period').innerHTML = acf['תקופה']
  // TODO Add images
  document.getElementById('history').innerHTML = acf.history
  document.getElementById('color').innerHTML = acf.characteristics.colors.join(' ,')
  document.getElementById('field').innerHTML = acf.characteristics.field
  document.getElementById('ethnicity').innerHTML = acf.characteristics.ethnicity
  document.getElementById('materials').innerHTML = acf.characteristics.materials.join(' ,')
  document.getElementById('width').innerHTML = acf.characteristics.size.width
  document.getElementById('length').innerHTML = acf.characteristics.size.length
  document.getElementById('height').innerHTML = acf.characteristics.size.height
  document.getElementById('diameter').innerHTML = acf.characteristics.size.diameter
  document.getElementById('depth').innerHTML = acf.characteristics.size.depth
  document.getElementById('general').innerHTML = acf.characteristics.size.general
  document.getElementById('physical-description').innerHTML = acf.condition.physical_description
  document.getElementById('report').innerHTML = acf.condition.report? reportToLinks(acf.condition.report) : ''
  document.getElementById('attribution').innerHTML = acf.attribution
  document.getElementById('uses').innerHTML = acf.uses
  document.getElementById('origin').innerHTML = acf.origin
  document.getElementById('status').innerHTML = acf.status
  document.getElementById('source').innerHTML = acf.source
  document.getElementById('phtographer').innerHTML = acf.phtographer? acf.phtographer : ''
  document.getElementById('box').innerHTML = acf.location.box
  document.getElementById('position').innerHTML = acf.location.position
  document.getElementById('room').innerHTML = acf.location.room
  document.getElementById('shelf').innerHTML = acf.location.shelf
  document.getElementById('registration-date').innerHTML = acf.registration_date
  document.getElementById('notes').innerHTML = acf.notes
  document.getElementById('registrant-name').innerHTML = acf.registrant_name
  document.getElementById('movements').innerHTML = movements != ""? acf.movements.map(m => movementToText(getMovementByID(m.ID))).join('\n') : ''
}

function reportToLinks(reports) {
  let str = ''
  reports.forEach(reportObj => {
    let report = reportObj.report_file
    if (str.length) str += '</br>'
    str += `<a href="${report.url}" target="_blank">${report.title}</a>`
  });
  return str
}

function movementToText(m) {
  if (m.title.rendered) return m.title.rendered
  let str = ''
  if (m.ACF.type) str += m.ACF.type + ' - '
  if (m.ACF.to) str += m.ACF.to + ' - '
  if (m.ACF.date) str += m.ACF.date + ' - '
  return str.slice(0, str.length - 3)
}

function expandImage(e) {
  let expandedImgContainer = document.getElementById('expanded-image-container')
  expandedImgContainer.classList.remove('hidden')
  expandedImgContainer.querySelector('img').src = e.target.src
}

function closeImage() {
  let expandedImgContainer = document.querySelector('#expanded-image-container')
  expandedImgContainer.classList.add('hidden')
  expandedImgContainer.querySelector('img').src = ''
}

init()
