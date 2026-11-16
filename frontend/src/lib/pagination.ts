// Page numbers to show: always the first and last page, the current page and its
// neighbours, with "…" for the gaps. E.g. page 5 of 10 -> 1 … 4 5 6 … 10
export function pageNumbers(current: number, last: number): (number | '…')[] {
  const pages: (number | '…')[] = []
  for (let page = 1; page <= last; page++) {
    if (page === 1 || page === last || Math.abs(page - current) <= 1) {
      pages.push(page)
    } else if (pages[pages.length - 1] !== '…') {
      pages.push('…')
    }
  }
  return pages
}
