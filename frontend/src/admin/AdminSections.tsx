import { useCallback, useEffect, useState } from 'react'
import { ApiError, apiRequest, apiSend } from '../api/client'
import FormField from '../components/FormField'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { useForm } from '../hooks/useForm'
import { formatDate, formatPrice } from '../lib/format'
import styles from './AdminSections.module.css'

interface AdminOffer {
  id: number
  product: string
  product_slug: string
  seller: string
  price_cents: number
  list_price_cents: number | null
  stock: number
  sold: number
}

interface AdminHomeBlock {
  id: number
  type: string
  title: string
  position: number
  enabled: boolean
  settings: Record<string, string | number>
}

interface AdminCoupon {
  id: number
  code: string
  percent_off: number
  expires_at: string | null
  max_uses: number | null
  times_used: number
  usable: boolean
}

interface AdminOrder {
  id: number
  created_at: string
  customer: string
  items: string[]
  coupon_code: string | null
  total_cents: number
}

// Loads an admin list and lets the tab reload it after a change.
function useAdminList<T>(path: string) {
  const [items, setItems] = useState<T[] | null>(null)
  const [error, setError] = useState<string | null>(null)

  const fetchList = useCallback(() => apiRequest<{ data: T[] }>(path), [path])
  const fail = (e: unknown) => setError(e instanceof ApiError ? e.message : 'Could not load.')

  useEffect(() => {
    fetchList()
      .then((response) => setItems(response.data))
      .catch(fail)
  }, [fetchList])

  const reload = () =>
    fetchList()
      .then((response) => setItems(response.data))
      .catch(fail)

  return { items, error, reload }
}

// /admin/offers: every seller's offer: change its price or paste in more keys.
export function OffersTab() {
  useDocumentTitle('Admin · Offers')
  const { items, error, reload } = useAdminList<AdminOffer>('/admin/offers')
  const [filter, setFilter] = useState('')
  if (error) return <p className="alert">{error}</p>
  if (!items) return <p>Loading…</p>

  const shown = items.filter((offer) =>
    `${offer.product} ${offer.seller}`.toLowerCase().includes(filter.trim().toLowerCase()),
  )

  return (
    <>
      <div className={styles.filterRow}>
        <label htmlFor="offer-filter">Find</label>
        <input
          id="offer-filter"
          placeholder="Product or seller"
          value={filter}
          onChange={(e) => setFilter(e.target.value)}
        />
        <span className={styles.muted}>
          {shown.length} of {items.length} offers
        </span>
      </div>
      <div className={styles.tableWrap}>
        <table className={styles.table}>
          <thead>
            <tr>
              <th scope="col">Product</th>
              <th scope="col">Seller</th>
              <th scope="col">Price (€)</th>
              <th scope="col">Stock</th>
              <th scope="col">Sold</th>
              <th scope="col">Keys</th>
            </tr>
          </thead>
          <tbody>
            {shown.map((offer) => (
              <OfferRow key={offer.id} offer={offer} onChanged={reload} />
            ))}
          </tbody>
        </table>
      </div>
    </>
  )
}

function OfferRow({ offer, onChanged }: { offer: AdminOffer; onChanged: () => void }) {
  const [price, setPrice] = useState((offer.price_cents / 100).toFixed(2))
  const [codes, setCodes] = useState('')
  const [showKeys, setShowKeys] = useState(false)
  const [note, setNote] = useState<string | null>(null)

  const toCents = (euros: string) => Math.round(Number(euros.replace(',', '.')) * 100)

  async function run(action: () => Promise<string>) {
    setNote(null)
    try {
      setNote(await action())
      onChanged()
    } catch (e) {
      setNote(e instanceof ApiError ? e.message : 'Failed.')
    }
  }

  return (
    <tr>
      <td>
        <strong>{offer.product}</strong>
        {note && <div className={styles.note}>{note}</div>}
        {showKeys && (
          <div className={styles.keysBox}>
            <label htmlFor={`keys-${offer.id}`}>Paste {offer.seller}'s keys, one per line</label>
            <textarea id={`keys-${offer.id}`} rows={4} value={codes} onChange={(e) => setCodes(e.target.value)} />
            <button
              type="button"
              className="btn btn-primary"
              disabled={codes.trim() === ''}
              onClick={() =>
                run(async () => {
                  const result = await apiSend<{ added: number }>('POST', `/admin/offers/${offer.id}/keys`, { codes })
                  setCodes('')
                  setShowKeys(false)
                  return `Added ${result.added} keys.`
                })
              }
            >
              Add keys
            </button>
          </div>
        )}
      </td>
      <td>{offer.seller}</td>
      <td>
        <form
          className={styles.priceForm}
          onSubmit={(e) => {
            e.preventDefault()
            run(async () => {
              await apiSend('PUT', `/admin/offers/${offer.id}`, { price_cents: toCents(price) })
              return 'Price saved.'
            })
          }}
        >
          <input
            aria-label={`${offer.seller}'s price for ${offer.product}`}
            inputMode="decimal"
            value={price}
            onChange={(e) => setPrice(e.target.value)}
          />
          <button type="submit" className="btn btn-outline">
            Save
          </button>
        </form>
        {offer.list_price_cents !== null && (
          <div className={styles.muted}>List price {formatPrice(offer.list_price_cents)}</div>
        )}
      </td>
      <td className={offer.stock === 0 ? styles.bad : ''}>{offer.stock}</td>
      <td>{offer.sold}</td>
      <td>
        <button type="button" className="link-button" onClick={() => setShowKeys((open) => !open)}>
          {showKeys ? 'Cancel' : 'Add keys'}
        </button>
      </td>
    </tr>
  )
}

// /admin/homepage: the homepage's blocks: rename, reorder (lower number = higher up) or hide them.
export function HomepageTab() {
  useDocumentTitle('Admin · Homepage')
  const { items, error, reload } = useAdminList<AdminHomeBlock>('/admin/home-blocks')
  if (error) return <p className="alert">{error}</p>
  if (!items) return <p>Loading…</p>

  return (
    <>
      <p className={styles.muted}>
        The homepage is built from these blocks, in this order. Changes show on the site right away.
      </p>
      <div className={styles.tableWrap}>
        <table className={styles.table}>
          <thead>
            <tr>
              <th scope="col">Position</th>
              <th scope="col">Title</th>
              <th scope="col">Type</th>
              <th scope="col">Settings</th>
              <th scope="col">Shown</th>
              <th scope="col">
                <span className="visually-hidden">Save</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {items.map((block) => (
              <HomeBlockRow key={block.id} block={block} onChanged={reload} />
            ))}
          </tbody>
        </table>
      </div>
    </>
  )
}

function HomeBlockRow({ block, onChanged }: { block: AdminHomeBlock; onChanged: () => void }) {
  const [title, setTitle] = useState(block.title)
  const [position, setPosition] = useState(String(block.position))
  const [enabled, setEnabled] = useState(block.enabled)
  const [note, setNote] = useState<string | null>(null)
  const formId = `block-${block.id}`

  async function save(event: React.FormEvent) {
    event.preventDefault()
    setNote(null)
    try {
      await apiSend('PUT', `/admin/home-blocks/${block.id}`, { title, position: Number(position), enabled })
      setNote('Saved.')
      onChanged()
    } catch (e) {
      setNote(e instanceof ApiError ? e.message : 'Failed.')
    }
  }

  return (
    <tr>
      <td>
        <input
          form={formId}
          className={styles.small}
          type="number"
          min={0}
          aria-label={`Position of ${block.title}`}
          value={position}
          onChange={(e) => setPosition(e.target.value)}
        />
      </td>
      <td>
        <input form={formId} aria-label={`Title of ${block.title}`} value={title} onChange={(e) => setTitle(e.target.value)} />
        {note && <div className={styles.note}>{note}</div>}
      </td>
      <td>{block.type}</td>
      <td className={styles.muted}>
        {Object.entries(block.settings)
          .map(([key, value]) => `${key}: ${value}`)
          .join(', ') || '—'}
      </td>
      <td>
        <input
          form={formId}
          type="checkbox"
          aria-label={`Show ${block.title}`}
          checked={enabled}
          onChange={(e) => setEnabled(e.target.checked)}
        />
      </td>
      <td>
        <form id={formId} onSubmit={save}>
          <button type="submit" className="btn btn-outline">
            Save
          </button>
        </form>
      </td>
    </tr>
  )
}

export function CouponsTab() {
  useDocumentTitle('Admin · Discount codes')
  const { items, error, reload } = useAdminList<AdminCoupon>('/admin/coupons')
  const [fields, setFields] = useState({ code: '', percent_off: '10', max_uses: '', expires_at: '' })
  const form = useForm()
  const set = (key: keyof typeof fields) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setFields((previous) => ({ ...previous, [key]: e.target.value }))

  if (error) return <p className="alert">{error}</p>

  return (
    <>
      <form
        className={styles.couponForm}
        noValidate
        onSubmit={form.submit(async () => {
          await apiSend('POST', '/admin/coupons', {
            code: fields.code,
            percent_off: Number(fields.percent_off),
            max_uses: fields.max_uses ? Number(fields.max_uses) : null,
            expires_at: fields.expires_at || null,
          })
          setFields({ code: '', percent_off: '10', max_uses: '', expires_at: '' })
          reload()
          return 'Code created.'
        })}
      >
        <FormField name="code" label="Code" value={fields.code} onChange={set('code')} errors={form.errors.code} />
        <FormField name="percent_off" label="% off" type="number" min={1} max={90} value={fields.percent_off} onChange={set('percent_off')} errors={form.errors.percent_off} />
        <FormField name="max_uses" label="Max uses (optional)" type="number" min={1} value={fields.max_uses} onChange={set('max_uses')} errors={form.errors.max_uses} />
        <FormField name="expires_at" label="Expires (optional)" type="date" value={fields.expires_at} onChange={set('expires_at')} errors={form.errors.expires_at} />
        <button type="submit" className="btn btn-primary" disabled={form.busy}>
          Create code
        </button>
        {form.success && <span className={styles.note}>{form.success}</span>}
      </form>

      {!items ? (
        <p>Loading…</p>
      ) : (
        <div className={styles.tableWrap}>
          <table className={styles.table}>
            <thead>
              <tr>
                <th scope="col">Code</th>
                <th scope="col">% off</th>
                <th scope="col">Used</th>
                <th scope="col">Expires</th>
                <th scope="col">Status</th>
                <th scope="col">
                  <span className="visually-hidden">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody>
              {items.map((coupon) => (
                <tr key={coupon.id}>
                  <td>
                    <code>{coupon.code}</code>
                  </td>
                  <td>{coupon.percent_off}%</td>
                  <td>
                    {coupon.times_used}
                    {coupon.max_uses !== null && ` / ${coupon.max_uses}`}
                  </td>
                  <td>{coupon.expires_at ? formatDate(coupon.expires_at) : 'Never'}</td>
                  <td className={coupon.usable ? styles.good : styles.bad}>{coupon.usable ? 'Active' : 'Used up / expired'}</td>
                  <td>
                    <button
                      type="button"
                      className="link-button"
                      onClick={() => apiSend('DELETE', `/admin/coupons/${coupon.id}`).then(reload)}
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  )
}

export function OrdersTab() {
  useDocumentTitle('Admin · Orders')
  const { items, error } = useAdminList<AdminOrder>('/admin/orders')
  if (error) return <p className="alert">{error}</p>
  if (!items) return <p>Loading…</p>
  if (items.length === 0) return <p>No orders yet.</p>

  return (
    <div className={styles.tableWrap}>
      <table className={styles.table}>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Date</th>
            <th scope="col">Customer</th>
            <th scope="col">Items</th>
            <th scope="col">Code</th>
            <th scope="col">Total</th>
          </tr>
        </thead>
        <tbody>
          {items.map((order) => (
            <tr key={order.id}>
              <td>{order.id}</td>
              <td>{formatDate(order.created_at)}</td>
              <td>{order.customer}</td>
              <td>{order.items.join(', ')}</td>
              <td>{order.coupon_code ?? '—'}</td>
              <td>{formatPrice(order.total_cents)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
