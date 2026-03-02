import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { bodyText, findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'
import AddressBookDialog from './AddressBookDialog.vue'

vi.mock('@/api/address-book', () => ({
  fetchAddressBook: vi.fn().mockResolvedValue({ data: { data: [] } }),
  createAddressBookEntry: vi.fn().mockResolvedValue({ data: { data: { id: 3, name: 'New', address: 'new-addr', created_at: '' } } }),
  deleteAddressBookEntry: vi.fn().mockResolvedValue({}),
}))

vi.mock('@/queries/address-book', () => ({
  ADDRESS_BOOK_QUERY_KEYS: { root: ['address-book'], list: () => ['address-book', 'list'] },
  addressBookListQuery: {
    key: ['address-book', 'list'],
    query: vi.fn().mockResolvedValue([]),
  },
}))

describe('AddressBookDialog.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  async function mountDialog (props: Record<string, unknown> = {}) {
    wrapper = mountWithPlugins(AddressBookDialog, {
      props: {
        modelValue: true,
        ...props,
      },
      attachTo: document.body,
    })
    await flushPromises()
    return wrapper
  }

  it('renders the dialog title', async () => {
    await mountDialog()

    expect(bodyText()).toContain(en.addressBook.title)
  })

  it('shows empty state when no entries', async () => {
    await mountDialog()

    const empty = findByTestId('address-book-empty')
    expect(empty).not.toBeNull()
    expect(bodyText()).toContain(en.addressBook.noEntries)
  })

  it('renders search field', async () => {
    await mountDialog()

    const search = findByTestId('address-book-search')
    expect(search).not.toBeNull()
  })

  it('renders add new button', async () => {
    await mountDialog()

    const addBtn = findByTestId('address-book-add-btn')
    expect(addBtn).not.toBeNull()
    expect(bodyText()).toContain(en.addressBook.addNew)
  })

  it('shows add form when add button is clicked', async () => {
    await mountDialog()

    const addBtn = findByTestId('address-book-add-btn')
    await addBtn!.trigger('click')
    await flushPromises()

    const nameInput = findByTestId('address-book-new-name')
    const addressInput = findByTestId('address-book-new-address')
    expect(nameInput).not.toBeNull()
    expect(addressInput).not.toBeNull()
  })

  it('save button is disabled when fields are empty', async () => {
    await mountDialog()

    const addBtn = findByTestId('address-book-add-btn')
    await addBtn!.trigger('click')
    await flushPromises()

    const saveBtn = findByTestId('address-book-save-btn')
    expect(saveBtn).not.toBeNull()
    expect((saveBtn!.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('has cancel/save buttons right-aligned', async () => {
    await mountDialog()

    const addBtn = findByTestId('address-book-add-btn')
    await addBtn!.trigger('click')
    await flushPromises()

    const saveBtn = findByTestId('address-book-save-btn')
    const btnContainer = saveBtn?.element.closest('.d-flex')
    expect(btnContainer?.classList.contains('justify-end')).toBe(true)
  })

  it('has a close button', async () => {
    await mountDialog()

    const closeBtn = findByTestId('address-book-close-btn')
    expect(closeBtn).not.toBeNull()
  })
})
