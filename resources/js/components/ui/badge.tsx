import * as React from "react"

import { cn } from "@/lib/utils"

function badgeVariants({
  variant = "default"
}: {
  variant?: "default" | "secondary" | "destructive" | "outline"
} = {}) {
  const baseClasses = "inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 aria-invalid:border-destructive transition-[color,box-shadow] overflow-auto"

  const variantClasses = {
    default: "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
    secondary: "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90",
    destructive: "border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20",
    outline: "text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground"
  }

  return `${baseClasses} ${variantClasses[variant]}`
}

function Badge({
  className,
  variant = "default",
  ...props
}: React.ComponentProps<"span"> & {
  variant?: "default" | "secondary" | "destructive" | "outline"
}) {
  return (
    <span
      data-slot="badge"
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
