import { cn } from "@/lib/utils";

import getDesignSystem from "../getDesignSystem";

/** Standard responsive page width container for future homepage sections. */
export default function PageContainer({ children, className, as: Tag = "div", ...props }) {
  const { layoutStyles } = getDesignSystem();

  return (
    <Tag className={cn(layoutStyles.pageContainer, className)} {...props}>
      {children}
    </Tag>
  );
}
