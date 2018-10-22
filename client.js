const API_URL = 'https://api.abraia.me'

const fetchGet = (url, auth) => {
  return new Promise((resolve, reject) => {
    fetch(url, {
      method: 'GET',
      mode: 'cors',
      headers: {
        'Authorization': auth
      }
    }).then(resp => resolve(resp))
      .catch(err => reject(err))
  })
}

class Client {
  constructor (apiKey, apiSecret) {
    if ((apiKey !== undefined) && (apiSecret !== undefined)) {
      this.setApiKeys(apiKey, apiSecret)
    }
  }

  setApiKeys (apiKey, apiSecret) {
    this.AUTHORIZATION = 'Basic ' + btoa(apiKey + ':' + apiSecret)
  }

  check () {
    return this.listFiles().then(resp => resp.folders[0].name)
  }

  listFiles (path = '') {
    return new Promise((resolve, reject) => {
      fetchGet(`${API_URL}/files/${path}`, this.AUTHORIZATION)
        .then(resp => resp.json()).then(resp => {
          const { files, folders } = resp
          for (const i in folders) {
            folders[i].path = folders[i].source
            folders[i].source = `${API_URL}/files/${folders[i].source}`
          }
          for (const i in files) {
            files[i].path = files[i].source
            files[i].source = `${API_URL}/files/${files[i].source}`
            files[i].thumbnail = `${API_URL}/files/${files[i].thumbnail}`
          }
          resolve({ files, folders })
        })
        .catch(err => reject(err))
    })
  }

  uploadFile (file, path = '', callback = undefined) {
    const source = path.endsWith('/') ? path + file.name : path
    const name = source.split('/').pop()
    const thumbnail = source.slice(0, -name.length) + 'tb_' + name
    return new Promise((resolve, reject) => {
      fetch(`${API_URL}/files/${path}`, {
        method: 'POST',
        mode: 'cors',
        headers: {
          'Authorization': this.AUTHORIZATION,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          name: file.name,
          type: file.type
        })
      }).then(resp => resp.json()).then(resp => {
        console.log(resp)
        const uploadURL = resp.uploadURL
        const xhr = new XMLHttpRequest()
        xhr.open('PUT', uploadURL, true)
        xhr.setRequestHeader('Content-type', file.type)
        xhr.upload.addEventListener('progress', (evt) => (callback instanceof Function) && callback(evt), false)
        xhr.onloadstart = () => console.log('start')
        xhr.onloadend = () => console.log('end')
        xhr.onload = () => {
          if (xhr.status === 200) {
            const resp = {
              name: name,
              path: source,
              source: `${API_URL}/files/${source}`,
              thumbnail: `${API_URL}/files/${thumbnail}`
            }
            resolve(resp)
          }
          reject(xhr.response)
        }
        xhr.send(file)
      }).catch(err => reject(err))
    })
  }

  downloadFile (path, params = {}) {
    const query = Object.entries(params).map(pair => pair.join('=')).join('&')
    const parsed = encodeURI(query).replace('#', '%23')
    const fullPath = query.length ? `${API_URL}/files/${path}?${parsed}` : `${API_URL}/files/${path}`
    console.log(fullPath)
    return new Promise((resolve, reject) => {
      fetchGet(fullPath, this.AUTHORIZATION)
        .then(resp => resp.arrayBuffer())
        .then(buffer => resolve(buffer))
        .catch(err => reject(err))
    })
  }

  removeFile (path) {
    return new Promise((resolve, reject) => {
      fetch(`${API_URL}/files/${path}`, {
        method: 'DELETE',
        mode: 'cors',
        headers: {
          'Authorization': this.AUTHORIZATION
        }
      }).then(resp => {
        //const file = resp.data.file
        //file.path = file.source
        //file.source = `${API_URL}/files/${file.source}`
        //resolve(file)
        resolve(resp.data)
      }).catch(err => reject(err))
    })
  }

  uploadImage (file, path = '', callback = undefined) {
    const formData = new FormData()
    formData.append('file', file)
    const url = `${API_URL}/images/${path}`
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest()
      xhr.open('POST', url, true)
      xhr.setRequestHeader('Authorization', this.AUTHORIZATION)
      xhr.upload.addEventListener('progress', (evt) => (callback instanceof Function) && callback(evt), false)
      xhr.onloadstart = () => console.log('start')
      xhr.onloadend = () => console.log('end')
      xhr.onload = () => {
        if (xhr.status === 200) {
          const resp = xhr.response.file
          resp.path = resp.source
          resp.source = `${API_URL}/images/${resp.source}`
          resp.thumbnail = `${API_URL}/images/${resp.thumbnail}`
          resolve(resp)
        }
        else reject(xhr.response)
      }
      xhr.send(formData)
    })
  }

  processVideo (path, params = {}) {
    const query = Object.entries(params).map(pair => pair.join('=')).join('&')
    const url = query.length ? `${API_URL}/videos/${path}?${query}` : `${API_URL}/videos/${path}`
    return new Promise((resolve, reject) => {
      fetchGet(url, this.AUTHORIZATION)
        .then(resp => resp.json())
        .then(json => resolve(json))
        .catch(err => reject(err))
    })
  }

  transformImage (path, params = {}) {
    const query = Object.entries(params).map(pair => pair.join('=')).join('&')
    const url = query.length ? `${API_URL}/images/${path}?${query}` : `${API_URL}/images/${path}`
    return new Promise((resolve, reject) => {
      fetchGet(url, this.AUTHORIZATION)
        .then(resp => resp.arrayBuffer())
        .then(buffer => resolve(buffer))
        .catch(err => reject(err))
    })
  }

  analyzeImage (path, params = {}) {
    const query = Object.entries(params).map(pair => pair.join('=')).join('&')
    const url = query.length ? `${API_URL}/analysis/${path}?${query}` : `${API_URL}/analysis/${path}`
    return new Promise((resolve, reject) => {
      fetchGet(url, this.AUTHORIZATION)
        .then(resp => resp.json())
        .then(resp => resolve({ status: 'success', result: resp.result }))
        .catch(err => reject({ status: 'error', error: err }))
    })
  }

  aestheticsImage (path, params = {}) {
    const query = Object.entries(params).map(pair => pair.join('=')).join('&')
    const url = query.length ? `${API_URL}/aesthetics/${path}?${query}` : `${API_URL}/aesthetics/${path}`
    return new Promise((resolve, reject) => {
      fetchGet(url, this.AUTHORIZATION)
        .then(resp => resp.json())
        .then(resp => resolve({ status: 'success', result: resp.result }))
        .catch(err => reject({ status: 'error', error: err }))
    })
  }
}
