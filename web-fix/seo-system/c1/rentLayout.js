import { cn } from '@/lib/utils';

/** Matches ShellPreviewHeader / ShellPreviewFooter outer content width at every breakpoint. */
export const RENT_SHELL_CONTAINER =
  'mx-auto w-full max-w-6xl px-sukoon-4 lg:px-sukoon-6';

export function RentShellContainer({ children, className, as: Tag = 'div', ...props }) {
  return (
    <Tag className={cn(RENT_SHELL_CONTAINER, className)} {...props}>
      {children}
    </Tag>
  );
}
