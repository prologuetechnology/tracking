import { clone, styles } from './reviewStyles'

// One browser-local record per staff account. Mutations run inside a read/write
// transaction so two tabs cannot allocate the same theme or revision number.
const initial = () => ({ themes: clone(styles), revisions: [] })
let opening
const database = () => {
  if (!opening)
    opening = new Promise((resolve, reject) => {
      const request = indexedDB.open(`prologue-portal-review-v1`, 1)
      request.onupgradeneeded = () =>
        request.result.createObjectStore(`workspaces`)
      request.onsuccess = () => resolve(request.result)
      request.onerror = () => {
        opening = null
        reject(request.error)
      }
    })
  return opening
}

export async function reviewArchive(owner, change) {
  const db = await database()
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(
      `workspaces`,
      change ? `readwrite` : `readonly`,
    )
    const store = transaction.objectStore(`workspaces`)
    const read = store.get(owner)
    let state
    let error
    read.onsuccess = () => {
      state = read.result ?? initial()
      if (change) {
        try {
          change(state)
          store.put(state, owner)
        } catch (cause) {
          error = cause
          transaction.abort()
        }
      }
    }
    transaction.oncomplete = () => resolve(state)
    transaction.onabort = () =>
      reject(
        error ??
          transaction.error ??
          new Error(`Local storage is unavailable.`),
      )
    transaction.onerror = () => reject(transaction.error)
  })
}
