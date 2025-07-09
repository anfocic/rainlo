import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-[color,box-shadow] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "btn-gradient-primary shadow-lg hover:shadow-xl",
        destructive:
          "bg-gradient-to-r from-red-500 to-red-600 text-white shadow-lg hover:shadow-xl hover:from-red-600 hover:to-red-700 focus-visible:ring-red-500/20",
        outline:
          "border border-input bg-gradient-card shadow-xs hover:bg-gradient-cool hover:text-accent-foreground",
        secondary:
          "btn-gradient-secondary shadow-lg hover:shadow-xl",
        ghost: "hover-gradient hover:text-accent-foreground",
        link: "text-gradient-primary underline-offset-4 hover:underline",
      },
      size: {
        default: "h-9 px-4 py-2 has-[>svg]:px-3",
        sm: "h-8 rounded-md px-3 has-[>svg]:px-2.5",
        lg: "h-10 rounded-md px-6 has-[>svg]:px-4",
        icon: "size-9",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  children,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const buttonClassName = cn(buttonVariants({ variant, size, className }))

  if (asChild) {
    // Filter out the asChild prop before cloning
    const { asChild: _, ...restProps } = props
    return React.cloneElement(children as React.ReactElement, {
      className: buttonClassName,
      "data-slot": "button",
      ...restProps,
    })
  }

  return (
    <button
      data-slot="button"
      className={buttonClassName}
      {...props}
    >
      {children}
    </button>
  )
}

export { Button, buttonVariants }
