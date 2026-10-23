/** Returns a transform that cuts text to at most `maxLength` characters. */
export function truncate(maxLength: number) {
  return (text: string) => text.slice(0, maxLength);
}
