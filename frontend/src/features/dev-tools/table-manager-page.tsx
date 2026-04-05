import { useEffect, useMemo, useRef, useState, type ChangeEvent } from 'react'
import { ChevronsUpDown, Database, Download, MoreHorizontal, PanelLeft, Pencil, Plus, RefreshCw, Search, Trash2, Upload } from 'lucide-react'
import { toast } from 'sonner'
import { tableApi } from '@/api/table'
import type {
  TableColumnRow,
  TableForeignKeyRow,
  TableIndexRow,
  TableListRow,
} from '@/api/table/types'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { SqlEditor } from '@/components/sql/sql-editor'
import { useAuthStore } from '@/stores/auth-store'

type ColumnDraft = {
  id: string
  name: string
  dataType: string
  length: string
  nullable: boolean
  defaultValue: string
  comment: string
  primary: boolean
  autoIncrement: boolean
  unsigned: boolean
  enumValues: string
  decimalPrecision: string
  decimalScale: string
  onUpdate: string
  unique: boolean
  indexed: boolean
}

type TableVisualDraft = {
  tableName: string
  tableComment: string
  engine: string
  charset: string
  collation: string
  rowFormat: string
  autoIncrement: string
  extraTableOptions: string
  extraConstraintsSql: string
  columns: ColumnDraft[]
  foreignKeys: ForeignKeyDraft[]
}

type ForeignKeyDraft = {
  id: string
  name: string
  columnName: string
  referenceTable: string
  referenceColumn: string
  onDelete: string
  onUpdate: string
}

type ExistingColumnDraft = ColumnDraft & {
  originalName: string
  dirty: boolean
  dropped: boolean
}

type IndexDraft = {
  id: string
  originalName: string
  name: string
  unique: boolean
  primary: boolean
  indexType: string
  comment: string
  columns: string
  dirty: boolean
  dropped: boolean
  isNew: boolean
}

type AlterForeignKeyDraft = ForeignKeyDraft & {
  originalName: string
  dirty: boolean
  dropped: boolean
  isNew: boolean
}

const DATA_TYPE_OPTIONS = [
  'BIGINT',
  'INT',
  'TINYINT',
  'DECIMAL',
  'ENUM',
  'SET',
  'VARCHAR',
  'TEXT',
  'LONGTEXT',
  'DATETIME',
  'TIMESTAMP',
  'DATE',
  'TIME',
  'JSON',
  'BOOLEAN',
  'BLOB',
]

const DEFAULT_COLLATION_BY_CHARSET: Record<string, string> = {
  utf8mb4: 'utf8mb4_unicode_ci',
  utf8: 'utf8_general_ci',
  latin1: 'latin1_swedish_ci',
}

function formatBytes(value: number) {
  if (!value) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let size = value
  let unitIndex = 0
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex += 1
  }
  return `${size.toFixed(size >= 10 ? 0 : 1)} ${units[unitIndex]}`
}

function formatDateTime(value?: string | null) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString()
}

function createDraftColumn(partial?: Partial<ColumnDraft>): ColumnDraft {
  return {
    id: crypto.randomUUID(),
    name: partial?.name ?? '',
    dataType: partial?.dataType ?? 'VARCHAR',
    length: partial?.length ?? '255',
    nullable: partial?.nullable ?? true,
    defaultValue: partial?.defaultValue ?? '',
    comment: partial?.comment ?? '',
    primary: partial?.primary ?? false,
    autoIncrement: partial?.autoIncrement ?? false,
    unsigned: partial?.unsigned ?? false,
    enumValues: partial?.enumValues ?? "'A','B'",
    decimalPrecision: partial?.decimalPrecision ?? '10',
    decimalScale: partial?.decimalScale ?? '2',
    onUpdate: partial?.onUpdate ?? '',
    unique: partial?.unique ?? false,
    indexed: partial?.indexed ?? false,
  }
}

function createForeignKeyDraft(partial?: Partial<ForeignKeyDraft>): ForeignKeyDraft {
  return {
    id: crypto.randomUUID(),
    name: partial?.name ?? '',
    columnName: partial?.columnName ?? '',
    referenceTable: partial?.referenceTable ?? '',
    referenceColumn: partial?.referenceColumn ?? '',
    onDelete: partial?.onDelete ?? 'RESTRICT',
    onUpdate: partial?.onUpdate ?? 'RESTRICT',
  }
}

function defaultCreateDraft(): TableVisualDraft {
  return {
    tableName: 'new_table',
    tableComment: '新建数据表',
    engine: 'InnoDB',
    charset: 'utf8mb4',
    collation: 'utf8mb4_unicode_ci',
    rowFormat: '',
    autoIncrement: '',
    extraTableOptions: '',
    extraConstraintsSql: '',
    columns: [
      createDraftColumn({
        name: 'id',
        dataType: 'BIGINT',
        nullable: false,
        primary: true,
        autoIncrement: true,
        comment: '主键',
      }),
      createDraftColumn({
        name: 'created_at',
        dataType: 'DATETIME',
        nullable: false,
        defaultValue: 'CURRENT_TIMESTAMP',
        comment: '创建时间',
      }),
      createDraftColumn({
        name: 'updated_at',
        dataType: 'DATETIME',
        nullable: false,
        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        comment: '更新时间',
      }),
    ],
    foreignKeys: [],
  }
}

function escapeSqlString(input: string) {
  return input.replace(/\\/g, '\\\\').replace(/'/g, "\\'")
}

function isNumberType(dataType: string) {
  return ['BIGINT', 'INT', 'TINYINT', 'DECIMAL'].includes(dataType.toUpperCase())
}

function normalizeColumnType(column: ColumnDraft) {
  const base = column.dataType.toUpperCase()
  const hasLength = Boolean(column.length.trim())
  let lengthPart = ''

  if (base === 'ENUM' || base === 'SET') {
    const raw = column.enumValues.trim()
    lengthPart = raw ? `(${raw})` : "('A','B')"
  } else if (base === 'DECIMAL') {
    const precision = column.decimalPrecision.trim() || '10'
    const scale = column.decimalScale.trim() || '2'
    lengthPart = `(${precision},${scale})`
  } else {
    const useLength = ['VARCHAR', 'BIGINT', 'INT', 'TINYINT'].includes(base)
    lengthPart = useLength && hasLength ? `(${column.length.trim()})` : ''
  }
  const unsignedPart = column.unsigned && isNumberType(base) ? ' UNSIGNED' : ''
  return `${base}${lengthPart}${unsignedPart}`
}

function normalizeDefaultClause(column: ColumnDraft) {
  const raw = column.defaultValue.trim()
  if (!raw) return ''

  const upper = raw.toUpperCase()
  if (upper === 'NULL') return ' DEFAULT NULL'
  if (upper.includes('CURRENT_TIMESTAMP')) return ` DEFAULT ${raw}`

  if (isNumberType(column.dataType) && !Number.isNaN(Number(raw))) {
    return ` DEFAULT ${raw}`
  }

  return ` DEFAULT '${escapeSqlString(raw)}'`
}

function buildColumnDefinition(column: ColumnDraft) {
  const name = column.name.trim()
  if (!name) return ''

  const parts = [`\`${name}\` ${normalizeColumnType(column)}`]
  parts.push(column.nullable ? 'NULL' : 'NOT NULL')

  const defaultClause = normalizeDefaultClause(column)
  if (defaultClause) parts.push(defaultClause.trim())

  if (column.autoIncrement) parts.push('AUTO_INCREMENT')
  if (column.onUpdate.trim()) parts.push(`ON UPDATE ${column.onUpdate.trim()}`)
  if (column.comment.trim()) parts.push(`COMMENT '${escapeSqlString(column.comment.trim())}'`)

  return `  ${parts.join(' ')}`
}

function buildCreateTableSql(draft: TableVisualDraft) {
  const tableName = draft.tableName.trim() || 'new_table'
  const columnDefs = draft.columns
    .map(buildColumnDefinition)
    .filter(Boolean)

  const primaryKeys = draft.columns
    .filter((c) => c.primary && c.name.trim())
    .map((c) => `\`${c.name.trim()}\``)

  if (primaryKeys.length > 0) {
    columnDefs.push(`  PRIMARY KEY (${primaryKeys.join(', ')})`)
  }

  draft.columns
    .filter((c) => c.unique && !c.primary && c.name.trim())
    .forEach((column) => {
      const col = column.name.trim()
      columnDefs.push(`  UNIQUE KEY \`uk_${col}\` (\`${col}\`)`)
    })

  draft.columns
    .filter((c) => c.indexed && !c.primary && !c.unique && c.name.trim())
    .forEach((column) => {
      const col = column.name.trim()
      columnDefs.push(`  KEY \`idx_${col}\` (\`${col}\`)`)
    })

  draft.foreignKeys.forEach((fk) => {
    const name = fk.name.trim()
    const columnName = fk.columnName.trim()
    const refTable = fk.referenceTable.trim()
    const refColumn = fk.referenceColumn.trim()
    if (!name || !columnName || !refTable || !refColumn) return
    columnDefs.push(
      `  CONSTRAINT \`${name}\` FOREIGN KEY (\`${columnName}\`) REFERENCES \`${refTable}\` (\`${refColumn}\`) ON DELETE ${fk.onDelete} ON UPDATE ${fk.onUpdate}`
    )
  })

  const extraConstraintLines = draft.extraConstraintsSql
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => `  ${line.replace(/,$/, '')}`)
  if (extraConstraintLines.length > 0) {
    columnDefs.push(...extraConstraintLines)
  }

  const commentSql = draft.tableComment.trim()
    ? ` COMMENT='${escapeSqlString(draft.tableComment.trim())}'`
    : ''

  const options = [
    `ENGINE=${draft.engine || 'InnoDB'}`,
    `DEFAULT CHARSET=${draft.charset || 'utf8mb4'}`,
    `COLLATE=${draft.collation || 'utf8mb4_unicode_ci'}`,
  ]
  if (draft.rowFormat.trim()) options.push(`ROW_FORMAT=${draft.rowFormat.trim()}`)
  if (draft.autoIncrement.trim()) options.push(`AUTO_INCREMENT=${draft.autoIncrement.trim()}`)
  if (draft.extraTableOptions.trim()) options.push(draft.extraTableOptions.trim())

  return [
    `CREATE TABLE \`${tableName}\` (`,
    columnDefs.join(',\n') || '  `id` BIGINT NOT NULL AUTO_INCREMENT',
    `) ${options.join(' ')}${commentSql};`,
  ].join('\n')
}

function normalizeLengthFromColumnType(columnType: string) {
  const match = columnType.match(/\(([^)]+)\)/)
  return match ? match[1] : ''
}

function parseIndexColumns(text: string) {
  return text
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
}

function formatIndexColumnToken(token: string) {
  const cleaned = token.trim()
  if (!cleaned) return ''
  if (cleaned.startsWith('`') || cleaned.includes('(')) return cleaned
  return `\`${cleaned}\``
}

function buildAddIndexClause(index: IndexDraft) {
  const columnTokens = parseIndexColumns(index.columns).map(formatIndexColumnToken).filter(Boolean)
  if (columnTokens.length === 0) return ''

  if (index.primary) {
    return `ADD PRIMARY KEY (${columnTokens.join(', ')})`
  }

  const name = index.name.trim()
  if (!name) return ''
  const usingSql = index.indexType.trim() ? ` USING ${index.indexType.trim().toUpperCase()}` : ''
  const commentSql = index.comment.trim() ? ` COMMENT '${escapeSqlString(index.comment.trim())}'` : ''
  const uniqueSql = index.unique ? 'UNIQUE ' : ''
  return `ADD ${uniqueSql}INDEX \`${name}\` (${columnTokens.join(', ')})${usingSql}${commentSql}`
}

function buildAdvancedAlterTableSql(params: {
  tableName: string
  originalComment: string
  tableComment: string
  existingColumns: ExistingColumnDraft[]
  addColumns: ColumnDraft[]
  indexes: IndexDraft[]
  foreignKeys: AlterForeignKeyDraft[]
  extraClausesSql: string
}) {
  const tableName = params.tableName.trim()
  if (!tableName) return ''

  const clauses: string[] = []

  if (params.originalComment !== params.tableComment) {
    clauses.push(`COMMENT = '${escapeSqlString(params.tableComment)}'`)
  }

  params.existingColumns.forEach((column) => {
    if (!column.dirty && !column.dropped) return
    const originalName = column.originalName.trim()
    if (!originalName) return

    if (column.dropped) {
      clauses.push(`DROP COLUMN \`${originalName}\``)
      return
    }

    if (!column.name.trim()) return
    const definition = buildColumnDefinition(column).trim()
    if (!definition) return

    if (column.name.trim() !== originalName) {
      clauses.push(`CHANGE COLUMN \`${originalName}\` ${definition}`)
    } else {
      clauses.push(`MODIFY COLUMN ${definition}`)
    }
  })

  params.addColumns.forEach((column) => {
    const definition = buildColumnDefinition(column).trim()
    if (!definition) return
    clauses.push(`ADD COLUMN ${definition}`)
  })

  params.indexes.forEach((index) => {
    if (index.primary && index.isNew && !index.dirty) return
    if (!index.dirty && !index.dropped && !index.isNew) return

    if (index.dropped) {
      if (index.primary) {
        clauses.push('DROP PRIMARY KEY')
      } else if (index.originalName.trim()) {
        clauses.push(`DROP INDEX \`${index.originalName.trim()}\``)
      }
      return
    }

    if (!index.isNew && index.originalName.trim()) {
      if (index.primary) {
        clauses.push('DROP PRIMARY KEY')
      } else {
        clauses.push(`DROP INDEX \`${index.originalName.trim()}\``)
      }
    }

    const addClause = buildAddIndexClause(index)
    if (addClause) clauses.push(addClause)
  })

  params.foreignKeys.forEach((fk) => {
    const originalName = fk.originalName.trim()
    const name = fk.name.trim()
    const columnName = fk.columnName.trim()
    const refTable = fk.referenceTable.trim()
    const refColumn = fk.referenceColumn.trim()

    if (!fk.dirty && !fk.dropped && !fk.isNew) return

    if (fk.dropped) {
      if (originalName) clauses.push(`DROP FOREIGN KEY \`${originalName}\``)
      return
    }

    if (!fk.isNew && originalName) {
      clauses.push(`DROP FOREIGN KEY \`${originalName}\``)
    }

    if (!name || !columnName || !refTable || !refColumn) return

    clauses.push(
      `ADD CONSTRAINT \`${name}\` FOREIGN KEY (\`${columnName}\`) REFERENCES \`${refTable}\` (\`${refColumn}\`) ON DELETE ${fk.onDelete} ON UPDATE ${fk.onUpdate}`
    )
  })

  const extraClauses = params.extraClausesSql
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => line.replace(/,$/, ''))
  if (extraClauses.length > 0) {
    clauses.push(...extraClauses)
  }

  if (clauses.length === 0) {
    return ''
  }

  return `ALTER TABLE \`${tableName}\`\n  ${clauses.join(',\n  ')};`
}

function columnRowToDraft(row: TableColumnRow): ExistingColumnDraft {
  return {
    ...createDraftColumn({
      name: row.columnName,
      dataType: (row.dataType || 'VARCHAR').toUpperCase(),
      length: normalizeLengthFromColumnType(row.columnType || ''),
      nullable: row.isNullable === 'YES',
      defaultValue: row.columnDefault || '',
      comment: row.columnComment || '',
      primary: row.columnKey === 'PRI',
      autoIncrement: (row.extra || '').toLowerCase().includes('auto_increment'),
      unsigned: (row.columnType || '').toLowerCase().includes('unsigned'),
      unique: row.columnKey === 'UNI',
      indexed: row.columnKey === 'MUL',
    }),
    originalName: row.columnName,
    dirty: false,
    dropped: false,
  }
}

function indexRowToDraft(row: TableIndexRow): IndexDraft {
  const isPrimary = row.indexName.toUpperCase() === 'PRIMARY'
  return {
    id: crypto.randomUUID(),
    originalName: row.indexName,
    name: isPrimary ? 'PRIMARY' : row.indexName,
    unique: isPrimary ? true : row.unique,
    primary: isPrimary,
    indexType: row.indexType || 'BTREE',
    comment: row.indexComment || '',
    columns: row.columns
      .slice()
      .sort((a, b) => a.seqInIndex - b.seqInIndex)
      .map((item) => item.columnName)
      .join(', '),
    dirty: false,
    dropped: false,
    isNew: false,
  }
}

function foreignKeyRowToDraft(row: TableForeignKeyRow): AlterForeignKeyDraft {
  return {
    ...createForeignKeyDraft({
      name: row.constraintName,
      columnName: row.columnName,
      referenceTable: row.referencedTableName,
      referenceColumn: row.referencedColumnName,
      onDelete: row.deleteRule || 'RESTRICT',
      onUpdate: row.updateRule || 'RESTRICT',
    }),
    originalName: row.constraintName,
    dirty: false,
    dropped: false,
    isNew: false,
  }
}

export default function TableManagerPage() {
  const { auth } = useAuthStore()
  const canView = auth.permissions.includes('system:table:page')
  const canList = auth.permissions.includes('system:table:list')
  const canCreate = auth.permissions.includes('system:table:create')
  const canEdit = auth.permissions.includes('system:table:edit')
  const canDelete = auth.permissions.includes('system:table:delete') || canEdit

  const [keyword, setKeyword] = useState('')
  const [appliedKeyword, setAppliedKeyword] = useState('')
  const [list, setList] = useState<TableListRow[]>([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [pageSize] = useState(20)
  const [loading, setLoading] = useState(false)

  const [selectedTable, setSelectedTable] = useState<string>('')
  const [mobileListOpen, setMobileListOpen] = useState(false)
  const [columns, setColumns] = useState<TableColumnRow[]>([])
  const [detailIndexes, setDetailIndexes] = useState<TableIndexRow[]>([])
  const [detailForeignKeys, setDetailForeignKeys] = useState<TableForeignKeyRow[]>([])
  const [loadingDetail, setLoadingDetail] = useState(false)

  const [createOpen, setCreateOpen] = useState(false)
  const [createTab, setCreateTab] = useState('visual')
  const [createDraft, setCreateDraft] = useState<TableVisualDraft>(defaultCreateDraft())
  const [createSqlDraft, setCreateSqlDraft] = useState(buildCreateTableSql(defaultCreateDraft()))

  const [alterOpen, setAlterOpen] = useState(false)
  const [alterTab, setAlterTab] = useState('visual')
  const [alterComment, setAlterComment] = useState('')
  const [alterOriginalComment, setAlterOriginalComment] = useState('')
  const [alterExistingColumns, setAlterExistingColumns] = useState<ExistingColumnDraft[]>([])
  const [alterAddColumns, setAlterAddColumns] = useState<ColumnDraft[]>([])
  const [alterIndexes, setAlterIndexes] = useState<IndexDraft[]>([])
  const [alterForeignKeys, setAlterForeignKeys] = useState<AlterForeignKeyDraft[]>([])
  const [referenceTableColumns, setReferenceTableColumns] = useState<Record<string, string[]>>({})
  const [alterExtraClausesSql, setAlterExtraClausesSql] = useState('')
  const [alterSqlDraft, setAlterSqlDraft] = useState('')

  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false)
  const [truncateConfirmOpen, setTruncateConfirmOpen] = useState(false)
  const [importOpen, setImportOpen] = useState(false)
  const [importFile, setImportFile] = useState<File | null>(null)
  const [importFileName, setImportFileName] = useState('')
  const [importMode, setImportMode] = useState<'strict' | 'skip-create'>('skip-create')
  const importFileInputRef = useRef<HTMLInputElement | null>(null)

  const totalPages = Math.max(1, Math.ceil(total / pageSize))
  const selectedTableInfo = useMemo(
    () => list.find((item) => item.tableName === selectedTable) || null,
    [list, selectedTable]
  )
  const availableTableColumns = useMemo(() => {
    const names = new Set<string>()
    alterExistingColumns
      .filter((column) => !column.dropped && column.name.trim())
      .forEach((column) => names.add(column.name.trim()))
    alterAddColumns
      .filter((column) => column.name.trim())
      .forEach((column) => names.add(column.name.trim()))
    return Array.from(names)
  }, [alterExistingColumns, alterAddColumns])

  const createSqlPreview = useMemo(() => buildCreateTableSql(createDraft), [createDraft])
  const alterSqlPreview = useMemo(
    () =>
      buildAdvancedAlterTableSql({
        tableName: selectedTable,
        originalComment: alterOriginalComment,
        tableComment: alterComment,
        existingColumns: alterExistingColumns,
        addColumns: alterAddColumns,
        indexes: alterIndexes,
        foreignKeys: alterForeignKeys,
        extraClausesSql: alterExtraClausesSql,
      }),
    [
      selectedTable,
      alterOriginalComment,
      alterComment,
      alterExistingColumns,
      alterAddColumns,
      alterIndexes,
      alterForeignKeys,
      alterExtraClausesSql,
    ]
  )

  async function loadTables() {
    if (!canView || !canList) return
    setLoading(true)
    try {
      const data = await tableApi.list({ page, pageSize, keyword: appliedKeyword })
      setList(data.list)
      setTotal(data.total)
      setSelectedTable((prev) => {
        if (prev && data.list.some((item) => item.tableName === prev)) return prev
        return data.list[0]?.tableName || ''
      })
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '加载数据表失败')
    } finally {
      setLoading(false)
    }
  }

  async function loadTableDetail(tableName: string) {
    if (!tableName) {
      setColumns([])
      setDetailIndexes([])
      setDetailForeignKeys([])
      return
    }

    setLoadingDetail(true)
    try {
      const [columnsRes, indexesRes, foreignKeysRes] = await Promise.allSettled([
        tableApi.columns(tableName),
        tableApi.indexes(tableName),
        tableApi.foreignKeys(tableName),
      ])

      if (columnsRes.status === 'fulfilled') {
        setColumns(columnsRes.value)
      } else {
        setColumns([])
        toast.error(columnsRes.reason instanceof Error ? columnsRes.reason.message : '加载字段结构失败')
      }

      if (indexesRes.status === 'fulfilled') {
        setDetailIndexes(indexesRes.value)
      } else {
        setDetailIndexes([])
        toast.error(indexesRes.reason instanceof Error ? indexesRes.reason.message : '加载索引失败')
      }

      if (foreignKeysRes.status === 'fulfilled') {
        setDetailForeignKeys(foreignKeysRes.value)
      } else {
        setDetailForeignKeys([])
        toast.error(foreignKeysRes.reason instanceof Error ? foreignKeysRes.reason.message : '加载外键失败')
      }
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '加载数据表详情失败')
    } finally {
      setLoadingDetail(false)
    }
  }

  useEffect(() => {
    void loadTables()
  }, [canView, canList, page, pageSize, appliedKeyword])

  useEffect(() => {
    if (page > totalPages) setPage(totalPages)
  }, [page, totalPages])

  useEffect(() => {
    void loadTableDetail(selectedTable)
  }, [selectedTable])

  function handleSearch() {
    setPage(1)
    setAppliedKeyword(keyword.trim())
  }

  function handleReset() {
    setKeyword('')
    setAppliedKeyword('')
    setPage(1)
  }

  function openCreateDialog() {
    const draft = defaultCreateDraft()
    setCreateDraft(draft)
    setCreateSqlDraft(buildCreateTableSql(draft))
    setCreateTab('visual')
    setCreateOpen(true)
  }

  function openAlterDialog() {
    const currentComment = selectedTableInfo?.tableComment || ''
    setAlterOriginalComment(currentComment)
    setAlterComment(currentComment)
    setAlterExistingColumns(columns.map(columnRowToDraft))
    setAlterAddColumns([])
    setAlterIndexes([])
    setAlterForeignKeys([])
    setReferenceTableColumns({})
    setAlterExtraClausesSql('')
    setAlterSqlDraft('')
    setAlterTab('visual')
    setAlterOpen(true)
    void (async () => {
      try {
        const [indexes, foreignKeys] = await Promise.all([
          tableApi.indexes(selectedTable),
          tableApi.foreignKeys(selectedTable),
        ])
        setAlterIndexes(indexes.map(indexRowToDraft))
        const fkDrafts = foreignKeys.map(foreignKeyRowToDraft)
        setAlterForeignKeys(fkDrafts)
        const refTables = Array.from(
          new Set(
            fkDrafts
              .map((fk) => fk.referenceTable.trim())
              .filter(Boolean)
          )
        )
        if (refTables.length > 0) {
          const entries = await Promise.all(
            refTables.map(async (tableName) => {
              try {
                const cols = await tableApi.columns(tableName)
                return [tableName, cols.map((col) => col.columnName)] as const
              } catch {
                return [tableName, []] as const
              }
            })
          )
          setReferenceTableColumns(Object.fromEntries(entries))
        }
      } catch (error) {
        toast.error(error instanceof Error ? error.message : '加载索引/外键信息失败')
      }
    })()
  }

  async function handleCreateTable() {
    const executableSql = createTab === 'visual' ? createSqlPreview : createSqlDraft
    if (!executableSql.trim()) {
      toast.error('SQL 为空，请先完善字段配置')
      return
    }
    try {
      await tableApi.createBySql(executableSql)
      toast.success('数据表创建成功')
      setCreateOpen(false)
      await loadTables()
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '创建数据表失败')
    }
  }

  async function handleAlterTable() {
    const executableSql = alterTab === 'visual' ? alterSqlPreview : alterSqlDraft
    if (!executableSql.trim()) {
      toast.error('未检测到可执行修改，请先调整字段或索引配置')
      return
    }

    if (alterTab === 'visual') {
      const droppedPrimary = alterIndexes.some((index) => index.primary && index.dropped)
      const hasNewPrimary = alterIndexes.some(
        (index) =>
          index.primary &&
          !index.dropped &&
          (index.isNew || index.dirty || !index.originalName)
      )
      const hasAutoIncrementColumn =
        alterExistingColumns.some((column) => !column.dropped && column.autoIncrement) ||
        alterAddColumns.some((column) => column.autoIncrement)

      if (droppedPrimary && hasAutoIncrementColumn && !hasNewPrimary) {
        toast.error('当前存在 AUTO_INCREMENT 字段，删除主键前请先新增主键/索引，或先取消该字段自增。')
        return
      }
    }

    try {
      await tableApi.alterBySql(executableSql)
      toast.success('数据表修改成功')
      setAlterOpen(false)
      if (selectedTable) await loadTableDetail(selectedTable)
      await loadTables()
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '修改数据表失败')
    }
  }

  async function handleDeleteTable() {
    if (!selectedTable) return
    try {
      await tableApi.remove(selectedTable)
      toast.success('数据表删除成功')
      setDeleteConfirmOpen(false)
      await loadTables()
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '删除数据表失败')
    }
  }

  async function handleTruncateTable() {
    if (!selectedTable) return
    try {
      await tableApi.truncate(selectedTable)
      toast.success('数据表已清空')
      setTruncateConfirmOpen(false)
      await loadTableDetail(selectedTable)
      await loadTables()
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '清空数据表失败')
    }
  }

  async function handleExportTableData() {
    if (!selectedTable) return
    try {
      const data = await tableApi.exportData(selectedTable)
      const blob = new Blob([data.sql], { type: 'application/sql;charset=utf-8' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = data.fileName || `${selectedTable}.sql`
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
      toast.success('导出成功')
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '导出失败')
    }
  }

  async function handleExportAllTableData() {
    try {
      const data = await tableApi.exportAllData()
      const blob = new Blob([data.sql], { type: 'application/sql;charset=utf-8' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = data.fileName || 'all-tables.sql'
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
      toast.success(`全量导出成功，共 ${data.tableCount} 张表`)
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '全量导出失败')
    }
  }

  async function handleImportSql() {
    if (!importFile) {
      toast.error('请先选择 SQL 文件')
      return
    }
    try {
      const result = await tableApi.importSqlFile(importFile, importMode)
      if (result.skippedCount > 0) {
        toast.success(
          `导入完成，执行 ${result.count} 条，跳过 ${result.skippedCount} 条（已存在表：${result.skippedTables.join('、')}）`
        )
      } else {
        toast.success(`导入成功，执行 ${result.count} 条 SQL`)
      }
      setImportOpen(false)
      setImportFile(null)
      setImportFileName('')
      setImportMode('skip-create')
      await loadTables()
      if (selectedTable) await loadTableDetail(selectedTable)
    } catch (error) {
      toast.error(error instanceof Error ? error.message : '导入失败')
    }
  }

  async function handleImportFileChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0]
    if (!file) return

    if (!file.name.toLowerCase().endsWith('.sql')) {
      toast.error('仅支持导入 .sql 文件')
      event.target.value = ''
      return
    }

    try {
      setImportFile(file)
      setImportFileName(file.name)
      toast.success(`已加载文件：${file.name}`)
    } catch {
      toast.error('读取 SQL 文件失败')
    } finally {
      event.target.value = ''
    }
  }

  function patchCreateColumn(id: string, patch: Partial<ColumnDraft>) {
    setCreateDraft((prev) => ({
      ...prev,
      columns: prev.columns.map((column) =>
        column.id === id ? { ...column, ...patch } : column
      ),
    }))
  }

  function patchAlterColumn(id: string, patch: Partial<ColumnDraft>) {
    setAlterAddColumns((prev) =>
      prev.map((column) => (column.id === id ? { ...column, ...patch } : column))
    )
  }

  function patchAlterExistingColumn(id: string, patch: Partial<ExistingColumnDraft>) {
    setAlterExistingColumns((prev) =>
      prev.map((column) =>
        column.id === id ? { ...column, ...patch, dirty: true } : column
      )
    )
  }

  function patchAlterIndex(id: string, patch: Partial<IndexDraft>) {
    setAlterIndexes((prev) =>
      prev.map((index) => (index.id === id ? { ...index, ...patch, dirty: true } : index))
    )
  }

  function patchAlterForeignKey(id: string, patch: Partial<AlterForeignKeyDraft>) {
    setAlterForeignKeys((prev) =>
      prev.map((fk) => (fk.id === id ? { ...fk, ...patch, dirty: true } : fk))
    )
  }

  async function ensureReferenceColumnsLoaded(tableName: string) {
    const normalized = tableName.trim()
    if (!normalized || referenceTableColumns[normalized]) return
    try {
      const columns = await tableApi.columns(normalized)
      setReferenceTableColumns((prev) => ({
        ...prev,
        [normalized]: columns.map((column) => column.columnName),
      }))
    } catch {
      setReferenceTableColumns((prev) => ({ ...prev, [normalized]: [] }))
      toast.error(`加载引用表字段失败：${normalized}`)
    }
  }

  function toggleIndexColumn(indexId: string, columnName: string, checked: boolean) {
    setAlterIndexes((prev) =>
      prev.map((index) => {
        if (index.id !== indexId) return index
        const current = new Set(parseIndexColumns(index.columns))
        if (checked) current.add(columnName)
        else current.delete(columnName)
        const ordered = availableTableColumns.filter((name) => current.has(name))
        return {
          ...index,
          columns: ordered.join(', '),
          dirty: true,
        }
      })
    )
  }

  function renderTableList(isMobile = false) {
    return (
      <div className='flex h-full min-h-0 flex-col rounded-md border'>
        <div className='flex items-center justify-between gap-2 border-b px-4 py-3'>
          <span className='text-sm text-muted-foreground'>共 {total} 张表</span>
          <Button
            size='sm'
            variant='outline'
            disabled={!canList || loading}
            onClick={() => void handleExportAllTableData()}
          >
            <Download className='mr-1 h-4 w-4' />
            导出全部
          </Button>
        </div>
        <div className='min-h-0 flex-1 overflow-auto'>
          {loading ? (
            <div className='px-4 py-6 text-sm text-muted-foreground'>加载中...</div>
          ) : list.length === 0 ? (
            <div className='px-4 py-6 text-sm text-muted-foreground'>暂无数据表</div>
          ) : (
            <div className='divide-y'>
              {list.map((item) => {
                const active = item.tableName === selectedTable
                return (
                  <button
                    key={item.tableName}
                    type='button'
                    className={`w-full px-4 py-3 text-left transition-colors ${
                      active ? 'bg-accent' : 'hover:bg-muted/40'
                    }`}
                    onClick={() => {
                      setSelectedTable(item.tableName)
                      if (isMobile) setMobileListOpen(false)
                    }}
                  >
                    <div className='flex items-center gap-2 font-medium'>
                      <Database className='h-4 w-4 text-muted-foreground' />
                      <span className='truncate'>{item.tableName}</span>
                    </div>
                    <div className='mt-1 truncate text-xs text-muted-foreground'>
                      {item.tableComment || '无注释'}
                    </div>
                    <div className='mt-1 text-xs text-muted-foreground'>
                      {item.engine || '-'} · {formatBytes((item.dataLength || 0) + (item.indexLength || 0))}
                    </div>
                  </button>
                )
              })}
            </div>
          )}
        </div>

        <div className='flex items-center justify-end gap-2 border-t px-4 py-3'>
          <Button variant='outline' size='sm' disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            上一页
          </Button>
          <span className='text-sm text-muted-foreground'>
            第 {page}/{totalPages} 页
          </span>
          <Button
            variant='outline'
            size='sm'
            disabled={page >= totalPages}
            onClick={() => setPage((p) => p + 1)}
          >
            下一页
          </Button>
        </div>
      </div>
    )
  }

  return (
    <>
      <Main className='space-y-4 px-4 py-5'>
        {!canView ? (
          <div className='rounded-md border p-4 text-sm text-muted-foreground'>
            当前账号没有数据表管理页面权限。
          </div>
        ) : (
          <>
            <div>
              <h2 className='text-2xl font-bold tracking-tight'>数据表管理</h2>
              <p className='text-muted-foreground'>
                查询系统全部数据表，仅展示表结构、索引与外键关系；新建/编辑请使用右上角弹窗。
              </p>
            </div>

            <div className='flex flex-col gap-2 xl:flex-row xl:items-center'>
              <div className='flex w-full flex-wrap gap-2 xl:w-auto'>
                <Button
                  variant='outline'
                  className='md:hidden'
                  onClick={() => setMobileListOpen(true)}
                >
                  <PanelLeft className='mr-1 h-4 w-4' />
                  数据表列表
                </Button>
                <Input
                  placeholder='搜索表名/注释'
                  value={keyword}
                  onChange={(e) => setKeyword(e.target.value)}
                  className='w-full sm:w-80'
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') handleSearch()
                  }}
                />
                <Button onClick={handleSearch}>
                  <Search className='mr-1 h-4 w-4' />搜索
                </Button>
                <Button variant='outline' onClick={handleReset}>重置</Button>
              </div>

              <div className='flex w-full flex-wrap items-center gap-2 xl:ms-auto xl:w-auto xl:justify-end'>
                <Button
                  size='sm'
                  variant='outline'
                  disabled={!canEdit || !selectedTable}
                  onClick={openAlterDialog}
                >
                  <Pencil className='mr-1 h-4 w-4' />编辑
                </Button>
                <Button size='sm' disabled={!canCreate} onClick={openCreateDialog}>
                  <Plus className='mr-1 h-4 w-4' />新建
                </Button>
                <Button size='sm' variant='outline' onClick={() => void loadTables()}>
                  <RefreshCw className='mr-1 h-4 w-4' />刷新
                </Button>

                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button size='sm' variant='outline'>
                      <MoreHorizontal className='mr-1 h-4 w-4' />
                      更多操作
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align='end' className='min-w-44'>
                    <DropdownMenuItem
                      disabled={!canList || !selectedTable}
                      onClick={() => void handleExportTableData()}
                    >
                      <Download className='mr-2 h-4 w-4' />
                      导出数据
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      disabled={!canCreate}
                      onClick={() => setImportOpen(true)}
                    >
                      <Upload className='mr-2 h-4 w-4' />
                      导入数据
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      className='text-destructive focus:text-destructive'
                      disabled={!canDelete || !selectedTable}
                      onClick={() => setTruncateConfirmOpen(true)}
                    >
                      <Trash2 className='mr-2 h-4 w-4' />
                      清空数据表
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      className='text-destructive focus:text-destructive'
                      disabled={!canDelete || !selectedTable}
                      onClick={() => setDeleteConfirmOpen(true)}
                    >
                      <Trash2 className='mr-2 h-4 w-4' />
                      删除数据表
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>

            <div className='grid gap-4 2xl:grid-cols-[360px_minmax(0,1fr)]'>
              <div className='hidden min-w-0 md:block'>
                {renderTableList()}
              </div>

              <div className='min-w-0 rounded-md border'>
                <div className='border-b px-4 py-3'>
                  <div className='font-medium'>{selectedTable || '请选择数据表'}</div>
                  <div className='mt-1 text-xs text-muted-foreground'>
                    {selectedTableInfo
                      ? `${selectedTableInfo.tableComment || '无注释'} · 创建于 ${formatDateTime(selectedTableInfo.createTime)}`
                      : '选择左侧数据表后查看结构、索引、外键关系与 SQL'}
                  </div>
                </div>

                <Tabs defaultValue='columns' className='p-4'>
                  <TabsList>
                    <TabsTrigger value='columns'>字段结构</TabsTrigger>
                    <TabsTrigger value='indexes'>索引</TabsTrigger>
                    <TabsTrigger value='foreign-keys'>外键关系</TabsTrigger>
                  </TabsList>

                  <TabsContent value='columns' className='mt-4'>
                    {loadingDetail ? (
                      <div className='text-sm text-muted-foreground'>加载详情中...</div>
                    ) : columns.length === 0 ? (
                      <div className='text-sm text-muted-foreground'>暂无字段信息</div>
                    ) : (
                      <div className='max-h-[520px] overflow-auto rounded-md border'>
                        <Table className='min-w-[900px]'>
                          <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                            <TableRow>
                              <TableHead>序号</TableHead>
                              <TableHead>字段名</TableHead>
                              <TableHead>类型</TableHead>
                              <TableHead>可空</TableHead>
                              <TableHead>默认值</TableHead>
                              <TableHead>键</TableHead>
                              <TableHead>额外</TableHead>
                              <TableHead>注释</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {columns.map((column) => (
                              <TableRow key={column.columnName}>
                                <TableCell>{column.ordinalPosition}</TableCell>
                                <TableCell className='font-medium'>{column.columnName}</TableCell>
                                <TableCell>{column.columnType}</TableCell>
                                <TableCell>{column.isNullable === 'YES' ? '是' : '否'}</TableCell>
                                <TableCell>{column.columnDefault || '-'}</TableCell>
                                <TableCell>{column.columnKey || '-'}</TableCell>
                                <TableCell>{column.extra || '-'}</TableCell>
                                <TableCell>{column.columnComment || '-'}</TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </div>
                    )}
                  </TabsContent>

                  <TabsContent value='indexes' className='mt-4'>
                    {loadingDetail ? (
                      <div className='text-sm text-muted-foreground'>加载详情中...</div>
                    ) : detailIndexes.length === 0 ? (
                      <div className='text-sm text-muted-foreground'>暂无索引信息</div>
                    ) : (
                      <div className='max-h-[520px] overflow-auto rounded-md border'>
                        <Table className='min-w-[780px]'>
                          <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                            <TableRow>
                              <TableHead>索引名</TableHead>
                              <TableHead>唯一</TableHead>
                              <TableHead>类型</TableHead>
                              <TableHead>索引列</TableHead>
                              <TableHead>注释</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {detailIndexes.map((item) => (
                              <TableRow key={item.indexName}>
                                <TableCell className='font-medium'>{item.indexName}</TableCell>
                                <TableCell>{item.unique ? '是' : '否'}</TableCell>
                                <TableCell>{item.indexType}</TableCell>
                                <TableCell>
                                  {item.columns
                                    .slice()
                                    .sort((a, b) => a.seqInIndex - b.seqInIndex)
                                    .map((col) => col.columnName)
                                    .join(', ')}
                                </TableCell>
                                <TableCell>{item.indexComment || '-'}</TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </div>
                    )}
                  </TabsContent>

                  <TabsContent value='foreign-keys' className='mt-4'>
                    {loadingDetail ? (
                      <div className='text-sm text-muted-foreground'>加载详情中...</div>
                    ) : detailForeignKeys.length === 0 ? (
                      <div className='text-sm text-muted-foreground'>暂无外键关系</div>
                    ) : (
                      <div className='max-h-[520px] overflow-auto rounded-md border'>
                        <Table className='min-w-[860px]'>
                          <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                            <TableRow>
                              <TableHead>约束名</TableHead>
                              <TableHead>本表字段</TableHead>
                              <TableHead>引用表</TableHead>
                              <TableHead>引用字段</TableHead>
                              <TableHead>删除动作</TableHead>
                              <TableHead>更新动作</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {detailForeignKeys.map((item) => (
                              <TableRow key={item.constraintName}>
                                <TableCell className='font-medium'>{item.constraintName}</TableCell>
                                <TableCell>{item.columnName}</TableCell>
                                <TableCell>{item.referencedTableName}</TableCell>
                                <TableCell>{item.referencedColumnName}</TableCell>
                                <TableCell>{item.deleteRule}</TableCell>
                                <TableCell>{item.updateRule}</TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </div>
                    )}
                  </TabsContent>

                </Tabs>
              </div>
            </div>
          </>
        )}
      </Main>

      <Sheet open={mobileListOpen} onOpenChange={setMobileListOpen}>
        <SheetContent side='left' className='w-[88vw] max-w-[360px] p-0'>
          <SheetHeader className='border-b px-4 py-3'>
            <SheetTitle>数据表列表</SheetTitle>
          </SheetHeader>
          <div className='min-h-0 flex-1 overflow-hidden p-3'>
            {renderTableList(true)}
          </div>
        </SheetContent>
      </Sheet>

      <AlertDialog open={deleteConfirmOpen} onOpenChange={setDeleteConfirmOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>确认删除数据表？</AlertDialogTitle>
            <AlertDialogDescription>
              将永久删除表 <span className='font-medium text-foreground'>{selectedTable || '-'}</span> 及其全部数据，
              此操作不可恢复。
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>取消</AlertDialogCancel>
            <AlertDialogAction className='bg-destructive text-destructive-foreground hover:bg-destructive/90' onClick={() => void handleDeleteTable()}>
              确认删除
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog open={truncateConfirmOpen} onOpenChange={setTruncateConfirmOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>确认清空数据表？</AlertDialogTitle>
            <AlertDialogDescription>
              将清空表 <span className='font-medium text-foreground'>{selectedTable || '-'}</span> 的全部数据，
              但会保留表结构。
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>取消</AlertDialogCancel>
            <AlertDialogAction className='bg-destructive text-destructive-foreground hover:bg-destructive/90' onClick={() => void handleTruncateTable()}>
              确认清空
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <Dialog open={importOpen} onOpenChange={setImportOpen}>
        <DialogContent className='max-w-3xl'>
          <DialogHeader>
            <DialogTitle>导入数据（SQL 文件）</DialogTitle>
          </DialogHeader>
          <div className='space-y-3'>
            <input
              ref={importFileInputRef}
              type='file'
              accept='.sql'
              className='hidden'
              onChange={(e) => {
                void handleImportFileChange(e)
              }}
            />

            <div className='flex flex-wrap items-center gap-2'>
              <Button
                type='button'
                variant='outline'
                onClick={() => importFileInputRef.current?.click()}
              >
                <Upload className='mr-1 h-4 w-4' />
                选择 SQL 文件
              </Button>
              {importFileName ? (
                <>
                  <Badge variant='secondary'>{importFileName}</Badge>
                  <Button
                    type='button'
                    variant='ghost'
                    onClick={() => {
                      setImportFile(null)
                      setImportFileName('')
                    }}
                  >
                    清空
                  </Button>
                </>
              ) : (
                <span className='text-sm text-muted-foreground'>
                  仅支持 `.sql` 文件，可直接导入本页面导出的文件。
                </span>
              )}
            </div>

            <div className='space-y-2'>
              <Label>导入模式</Label>
              <Select
                value={importMode}
                onValueChange={(value) => setImportMode(value as 'strict' | 'skip-create')}
              >
                <SelectTrigger className='w-full'>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='skip-create'>智能导入（跳过已存在表）</SelectItem>
                  <SelectItem value='strict'>严格导入（存在同名表则报错）</SelectItem>
                </SelectContent>
              </Select>
              <p className='text-xs text-muted-foreground'>
                上传 SQL 文件后由后端解析并执行，不再通过请求参数明文传输 SQL。
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button
              variant='outline'
              onClick={() => {
                setImportOpen(false)
                setImportFile(null)
                setImportFileName('')
                setImportMode('skip-create')
              }}
            >
              取消
            </Button>
            <Button onClick={() => void handleImportSql()} disabled={!importFile}>
              <Upload className='mr-1 h-4 w-4' />
              执行导入
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className='max-h-[90vh] !w-[calc(100vw-2rem)] !max-w-[calc(100vw-2rem)] sm:!w-[calc(100vw-3rem)] sm:!max-w-[calc(100vw-3rem)] 2xl:!max-w-[1400px] overflow-y-auto'>
          <DialogHeader>
            <DialogTitle>新建数据表</DialogTitle>
          </DialogHeader>

          <Tabs value={createTab} onValueChange={setCreateTab} className='min-w-0 space-y-4'>
            <TabsList>
              <TabsTrigger value='visual'>可视化配置</TabsTrigger>
              <TabsTrigger value='sql'>SQL 编辑</TabsTrigger>
            </TabsList>

            <TabsContent value='visual' className='space-y-4'>
              <div className='grid gap-3 md:grid-cols-2'>
                <div className='space-y-2'>
                  <Label>表名</Label>
                  <Input
                    value={createDraft.tableName}
                    onChange={(e) => setCreateDraft((prev) => ({ ...prev, tableName: e.target.value }))}
                    placeholder='如：order_record'
                  />
                </div>
                <div className='space-y-2'>
                  <Label>注释</Label>
                  <Input
                    value={createDraft.tableComment}
                    onChange={(e) => setCreateDraft((prev) => ({ ...prev, tableComment: e.target.value }))}
                    placeholder='表用途说明'
                  />
                </div>
                <div className='space-y-2'>
                  <Label>引擎</Label>
                  <Select
                    value={createDraft.engine}
                    onValueChange={(value) => setCreateDraft((prev) => ({ ...prev, engine: value }))}
                  >
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value='InnoDB'>InnoDB</SelectItem>
                      <SelectItem value='MyISAM'>MyISAM</SelectItem>
                      <SelectItem value='MEMORY'>MEMORY</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className='space-y-2'>
                  <Label>字符集</Label>
                  <Select
                    value={createDraft.charset}
                    onValueChange={(value) =>
                      setCreateDraft((prev) => ({
                        ...prev,
                        charset: value,
                        collation: DEFAULT_COLLATION_BY_CHARSET[value] || prev.collation,
                      }))
                    }
                  >
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value='utf8mb4'>utf8mb4</SelectItem>
                      <SelectItem value='utf8'>utf8</SelectItem>
                      <SelectItem value='latin1'>latin1</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className='space-y-2 md:col-span-2'>
                  <Label>排序规则</Label>
                  <Input
                    value={createDraft.collation}
                    onChange={(e) => setCreateDraft((prev) => ({ ...prev, collation: e.target.value }))}
                    placeholder='如：utf8mb4_unicode_ci'
                  />
                </div>
              </div>

              <div className='rounded-md border'>
                <div className='flex items-center justify-between border-b px-3 py-2'>
                  <div className='text-sm font-medium'>字段配置</div>
                  <Button
                    variant='outline'
                    size='sm'
                    onClick={() => setCreateDraft((prev) => ({ ...prev, columns: [...prev.columns, createDraftColumn()] }))}
                  >
                    <Plus className='mr-1 h-4 w-4' />新增字段
                  </Button>
                </div>
                <div className='w-full max-h-[360px] overflow-y-auto overflow-x-auto'>
                  <Table className='min-w-[1720px] w-max [&_input]:min-w-[140px] [&_[data-slot=select-trigger]]:min-w-[140px]'>
                    <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                      <TableRow>
                        <TableHead className='min-w-[130px]'>字段名</TableHead>
                        <TableHead className='min-w-[140px]'>类型</TableHead>
                        <TableHead className='min-w-[170px]'>类型参数</TableHead>
                        <TableHead className='w-[90px]'>非空</TableHead>
                        <TableHead className='w-[90px]'>主键</TableHead>
                        <TableHead className='w-[90px]'>唯一</TableHead>
                        <TableHead className='w-[90px]'>索引</TableHead>
                        <TableHead className='w-[100px]'>自增</TableHead>
                        <TableHead className='min-w-[130px]'>ON UPDATE</TableHead>
                        <TableHead className='min-w-[150px]'>默认值</TableHead>
                        <TableHead className='min-w-[150px]'>注释</TableHead>
                        <TableHead className='w-[60px]'>删除</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {createDraft.columns.map((column) => (
                        <TableRow key={column.id}>
                          <TableCell>
                            <Input
                              value={column.name}
                              onChange={(e) => patchCreateColumn(column.id, { name: e.target.value })}
                              placeholder='column_name'
                            />
                          </TableCell>
                          <TableCell>
                            <Select
                              value={column.dataType}
                              onValueChange={(value) => patchCreateColumn(column.id, { dataType: value })}
                            >
                              <SelectTrigger><SelectValue /></SelectTrigger>
                              <SelectContent>
                                {DATA_TYPE_OPTIONS.map((opt) => (
                                  <SelectItem key={opt} value={opt}>{opt}</SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          </TableCell>
                          <TableCell>
                            {column.dataType === 'DECIMAL' ? (
                              <div className='grid grid-cols-2 gap-1'>
                                <Input
                                  value={column.decimalPrecision}
                                  onChange={(e) => patchCreateColumn(column.id, { decimalPrecision: e.target.value })}
                                  placeholder='精度'
                                />
                                <Input
                                  value={column.decimalScale}
                                  onChange={(e) => patchCreateColumn(column.id, { decimalScale: e.target.value })}
                                  placeholder='小数位'
                                />
                              </div>
                            ) : column.dataType === 'ENUM' || column.dataType === 'SET' ? (
                              <Input
                                value={column.enumValues}
                                onChange={(e) => patchCreateColumn(column.id, { enumValues: e.target.value })}
                                placeholder="'A','B'"
                              />
                            ) : (
                              <Input
                                value={column.length}
                                onChange={(e) => patchCreateColumn(column.id, { length: e.target.value })}
                                placeholder='255'
                              />
                            )}
                          </TableCell>
                          <TableCell>
                            <div className='flex justify-center'>
                              <Checkbox
                                checked={!column.nullable}
                                onCheckedChange={(checked) => patchCreateColumn(column.id, { nullable: !Boolean(checked) })}
                              />
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className='flex justify-center'>
                              <Checkbox
                                checked={column.primary}
                                onCheckedChange={(checked) => patchCreateColumn(column.id, { primary: Boolean(checked), nullable: false })}
                              />
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className='flex justify-center'>
                              <Checkbox
                                checked={column.unique}
                                onCheckedChange={(checked) => patchCreateColumn(column.id, { unique: Boolean(checked) })}
                                disabled={column.primary}
                              />
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className='flex justify-center'>
                              <Checkbox
                                checked={column.indexed}
                                onCheckedChange={(checked) => patchCreateColumn(column.id, { indexed: Boolean(checked) })}
                                disabled={column.primary || column.unique}
                              />
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className='flex justify-center'>
                              <Checkbox
                                checked={column.autoIncrement}
                                onCheckedChange={(checked) => patchCreateColumn(column.id, { autoIncrement: Boolean(checked), nullable: false })}
                              />
                            </div>
                          </TableCell>
                          <TableCell>
                            <Input
                              value={column.onUpdate}
                              onChange={(e) => patchCreateColumn(column.id, { onUpdate: e.target.value })}
                              placeholder='CURRENT_TIMESTAMP'
                            />
                          </TableCell>
                          <TableCell>
                            <Input
                              value={column.defaultValue}
                              onChange={(e) => patchCreateColumn(column.id, { defaultValue: e.target.value })}
                              placeholder='CURRENT_TIMESTAMP'
                            />
                          </TableCell>
                          <TableCell>
                            <Input
                              value={column.comment}
                              onChange={(e) => patchCreateColumn(column.id, { comment: e.target.value })}
                              placeholder='字段注释'
                            />
                          </TableCell>
                          <TableCell>
                            <Button
                              variant='ghost'
                              size='icon'
                              onClick={() => setCreateDraft((prev) => ({ ...prev, columns: prev.columns.filter((c) => c.id !== column.id) }))}
                            >
                              <Trash2 className='h-4 w-4 text-destructive' />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </div>

              <div className='space-y-2'>
                <div className='flex items-center justify-between'>
                  <Label>SQL 预览</Label>
                  <Button
                    variant='outline'
                    size='sm'
                    onClick={() => {
                      setCreateSqlDraft(createSqlPreview)
                      setCreateTab('sql')
                      toast.success('已同步预览 SQL 到编辑器')
                    }}
                  >
                    同步到 SQL 编辑器
                  </Button>
                </div>
                <SqlEditor
                  value={createSqlPreview}
                  readOnly
                  height={220}
                  className='overflow-hidden rounded-md border'
                />
              </div>
            </TabsContent>

            <TabsContent value='sql' className='space-y-2'>
              <Label>CREATE TABLE SQL</Label>
              <SqlEditor
                value={createSqlDraft}
                onChange={setCreateSqlDraft}
                height={520}
                className='overflow-hidden rounded-md border'
              />
            </TabsContent>
          </Tabs>

          <DialogFooter>
            <Button variant='outline' onClick={() => setCreateOpen(false)}>取消</Button>
            <Button onClick={() => void handleCreateTable()}>执行创建</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={alterOpen} onOpenChange={setAlterOpen}>
        <DialogContent className='!flex !h-[90vh] !max-h-[90vh] !w-[calc(100vw-2rem)] !max-w-[calc(100vw-2rem)] flex-col overflow-hidden p-0 sm:!w-[calc(100vw-3rem)] sm:!max-w-[calc(100vw-3rem)] 2xl:!max-w-[1400px]'>
          <DialogHeader className='shrink-0 border-b px-6 py-4 pr-12'>
            <DialogTitle>编辑数据表（{selectedTable || '-' }）</DialogTitle>
          </DialogHeader>

          <div className='min-h-0 flex-1 overflow-hidden px-6 py-4'>
            <Tabs value={alterTab} onValueChange={setAlterTab} className='flex h-full min-h-0 min-w-0 flex-col gap-4'>
              <TabsList className='shrink-0'>
                <TabsTrigger value='visual'>可视化配置</TabsTrigger>
                <TabsTrigger value='sql'>SQL 编辑</TabsTrigger>
              </TabsList>

              <TabsContent value='visual' className='min-h-0 flex-1 space-y-4 overflow-y-auto pr-1'>
                <div className='grid gap-3 md:grid-cols-2'>
                  <div className='space-y-2'>
                    <Label>表名</Label>
                    <Input value={selectedTable} readOnly />
                  </div>
                  <div className='space-y-2'>
                    <Label>表注释</Label>
                    <Input
                      value={alterComment}
                      onChange={(e) => setAlterComment(e.target.value)}
                      placeholder='修改表注释'
                    />
                  </div>
                </div>
                <div className='rounded-md border p-3'>
                  <div className='mb-2 flex items-center justify-between'>
                    <div className='text-sm font-medium'>已有字段（支持修改 / CHANGE / MODIFY）</div>
                    <Badge variant='secondary'>{alterExistingColumns.length} 个字段</Badge>
                  </div>
                  <div className='w-full max-h-[360px] overflow-y-auto overflow-x-auto rounded-md border'>
                    <Table className='min-w-[1660px] w-max [&_input]:min-w-[140px] [&_[data-slot=select-trigger]]:min-w-[140px]'>
                      <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                        <TableRow>
                          <TableHead>字段名</TableHead>
                          <TableHead>类型</TableHead>
                          <TableHead>类型参数</TableHead>
                          <TableHead>可空</TableHead>
                          <TableHead>唯一</TableHead>
                          <TableHead>索引</TableHead>
                          <TableHead>默认值</TableHead>
                          <TableHead>ON UPDATE</TableHead>
                          <TableHead>注释</TableHead>
                          <TableHead>删除</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {alterExistingColumns.map((column) => (
                          <TableRow key={column.id}>
                            <TableCell>
                              <Input
                                value={column.name}
                                onChange={(e) => patchAlterExistingColumn(column.id, { name: e.target.value })}
                                placeholder='column_name'
                                disabled={column.dropped}
                              />
                            </TableCell>
                            <TableCell>
                              <Select
                                value={column.dataType}
                                onValueChange={(value) => patchAlterExistingColumn(column.id, { dataType: value })}
                                disabled={column.dropped}
                              >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                  {DATA_TYPE_OPTIONS.map((opt) => (
                                    <SelectItem key={opt} value={opt}>{opt}</SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                            </TableCell>
                            <TableCell>
                              {column.dataType === 'DECIMAL' ? (
                                <div className='grid grid-cols-2 gap-1'>
                                  <Input
                                    value={column.decimalPrecision}
                                    onChange={(e) =>
                                      patchAlterExistingColumn(column.id, { decimalPrecision: e.target.value })
                                    }
                                    disabled={column.dropped}
                                  />
                                  <Input
                                    value={column.decimalScale}
                                    onChange={(e) =>
                                      patchAlterExistingColumn(column.id, { decimalScale: e.target.value })
                                    }
                                    disabled={column.dropped}
                                  />
                                </div>
                              ) : column.dataType === 'ENUM' || column.dataType === 'SET' ? (
                                <Input
                                  value={column.enumValues}
                                  onChange={(e) => patchAlterExistingColumn(column.id, { enumValues: e.target.value })}
                                  disabled={column.dropped}
                                />
                              ) : (
                                <Input
                                  value={column.length}
                                  onChange={(e) => patchAlterExistingColumn(column.id, { length: e.target.value })}
                                  disabled={column.dropped}
                                />
                              )}
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={!column.nullable}
                                  onCheckedChange={(checked) =>
                                    patchAlterExistingColumn(column.id, { nullable: !Boolean(checked) })
                                  }
                                  disabled={column.dropped}
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={column.unique}
                                  onCheckedChange={(checked) =>
                                    patchAlterExistingColumn(column.id, { unique: Boolean(checked) })
                                  }
                                  disabled={column.dropped}
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={column.indexed}
                                  onCheckedChange={(checked) =>
                                    patchAlterExistingColumn(column.id, { indexed: Boolean(checked) })
                                  }
                                  disabled={column.dropped}
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <Input
                                value={column.defaultValue}
                                onChange={(e) => patchAlterExistingColumn(column.id, { defaultValue: e.target.value })}
                                disabled={column.dropped}
                              />
                            </TableCell>
                            <TableCell>
                              <Input
                                value={column.onUpdate}
                                onChange={(e) =>
                                  patchAlterExistingColumn(column.id, { onUpdate: e.target.value })
                                }
                                disabled={column.dropped}
                              />
                            </TableCell>
                            <TableCell>
                              <Input
                                value={column.comment}
                                onChange={(e) => patchAlterExistingColumn(column.id, { comment: e.target.value })}
                                disabled={column.dropped}
                              />
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={column.dropped}
                                  onCheckedChange={(checked) =>
                                    patchAlterExistingColumn(column.id, { dropped: Boolean(checked) })
                                  }
                                />
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                </div>

                <div className='rounded-md border'>
                  <div className='flex items-center justify-between border-b px-3 py-2'>
                    <div className='text-sm font-medium'>新增字段</div>
                    <Button
                      variant='outline'
                      size='sm'
                      onClick={() => setAlterAddColumns((prev) => [...prev, createDraftColumn()])}
                    >
                      <Plus className='mr-1 h-4 w-4' />新增字段
                    </Button>
                  </div>
                  <div className='w-full max-h-[360px] overflow-y-auto overflow-x-auto'>
                    <Table className='min-w-[1660px] w-max [&_input]:min-w-[140px] [&_[data-slot=select-trigger]]:min-w-[140px]'>
                      <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                        <TableRow>
                          <TableHead className='min-w-[130px]'>字段名</TableHead>
                          <TableHead className='min-w-[140px]'>类型</TableHead>
                          <TableHead className='min-w-[170px]'>类型参数</TableHead>
                          <TableHead className='w-[90px]'>非空</TableHead>
                          <TableHead className='w-[90px]'>唯一</TableHead>
                          <TableHead className='w-[90px]'>索引</TableHead>
                          <TableHead className='w-[100px]'>自增</TableHead>
                          <TableHead className='min-w-[130px]'>ON UPDATE</TableHead>
                          <TableHead className='min-w-[150px]'>默认值</TableHead>
                          <TableHead className='min-w-[150px]'>注释</TableHead>
                          <TableHead className='w-[60px]'>删除</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {alterAddColumns.map((column) => (
                          <TableRow key={column.id}>
                            <TableCell>
                              <Input
                                value={column.name}
                                onChange={(e) => patchAlterColumn(column.id, { name: e.target.value })}
                                placeholder='new_column'
                              />
                            </TableCell>
                            <TableCell>
                              <Select
                                value={column.dataType}
                                onValueChange={(value) => patchAlterColumn(column.id, { dataType: value })}
                              >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                  {DATA_TYPE_OPTIONS.map((opt) => (
                                    <SelectItem key={opt} value={opt}>{opt}</SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                            </TableCell>
                            <TableCell>
                              {column.dataType === 'DECIMAL' ? (
                                <div className='grid grid-cols-2 gap-1'>
                                  <Input
                                    value={column.decimalPrecision}
                                    onChange={(e) => patchAlterColumn(column.id, { decimalPrecision: e.target.value })}
                                  />
                                  <Input
                                    value={column.decimalScale}
                                    onChange={(e) => patchAlterColumn(column.id, { decimalScale: e.target.value })}
                                  />
                                </div>
                              ) : column.dataType === 'ENUM' || column.dataType === 'SET' ? (
                                <Input
                                  value={column.enumValues}
                                  onChange={(e) => patchAlterColumn(column.id, { enumValues: e.target.value })}
                                />
                              ) : (
                                <Input
                                  value={column.length}
                                  onChange={(e) => patchAlterColumn(column.id, { length: e.target.value })}
                                />
                              )}
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={!column.nullable}
                                  onCheckedChange={(checked) => patchAlterColumn(column.id, { nullable: !Boolean(checked) })}
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={column.unique}
                                  onCheckedChange={(checked) =>
                                    patchAlterColumn(column.id, { unique: Boolean(checked) })
                                  }
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={column.indexed}
                                  onCheckedChange={(checked) =>
                                    patchAlterColumn(column.id, { indexed: Boolean(checked) })
                                  }
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={column.autoIncrement}
                                  onCheckedChange={(checked) => patchAlterColumn(column.id, { autoIncrement: Boolean(checked), nullable: false })}
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <Input
                                value={column.onUpdate}
                                onChange={(e) => patchAlterColumn(column.id, { onUpdate: e.target.value })}
                              />
                            </TableCell>
                            <TableCell>
                              <Input
                                value={column.defaultValue}
                                onChange={(e) => patchAlterColumn(column.id, { defaultValue: e.target.value })}
                              />
                            </TableCell>
                            <TableCell>
                              <Input
                                value={column.comment}
                                onChange={(e) => patchAlterColumn(column.id, { comment: e.target.value })}
                              />
                            </TableCell>
                            <TableCell>
                              <Button
                                variant='ghost'
                                size='icon'
                                onClick={() => setAlterAddColumns((prev) => prev.filter((c) => c.id !== column.id))}
                              >
                                <Trash2 className='h-4 w-4 text-destructive' />
                              </Button>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                </div>

                <div className='rounded-md border'>
                  <div className='flex items-center justify-between border-b px-3 py-2'>
                    <div className='text-sm font-medium'>索引配置（支持新增/修改/删除）</div>
                    <Button
                      variant='outline'
                      size='sm'
                      onClick={() =>
                        setAlterIndexes((prev) => [
                          ...prev,
                          {
                            id: crypto.randomUUID(),
                            originalName: '',
                            name: '',
                            unique: false,
                            primary: false,
                            indexType: 'BTREE',
                            comment: '',
                            columns: '',
                            dirty: true,
                            dropped: false,
                            isNew: true,
                          },
                        ])
                      }
                    >
                      <Plus className='mr-1 h-4 w-4' />新增索引
                    </Button>
                  </div>
                  <div className='w-full max-h-[320px] overflow-y-auto overflow-x-auto'>
                    <Table className='min-w-[1200px] w-max [&_input]:min-w-[140px] [&_[data-slot=select-trigger]]:min-w-[140px]'>
                      <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                        <TableRow>
                          <TableHead className='min-w-[130px]'>索引名</TableHead>
                          <TableHead className='w-[90px]'>唯一</TableHead>
                          <TableHead className='w-[90px]'>主键</TableHead>
                          <TableHead className='w-[130px]'>索引类型</TableHead>
                          <TableHead className='min-w-[200px]'>索引列（逗号分隔）</TableHead>
                          <TableHead className='min-w-[160px]'>注释</TableHead>
                          <TableHead className='w-[80px]'>删除</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {alterIndexes.map((index) => (
                          <TableRow key={index.id}>
                            <TableCell>
                              <Input
                                value={index.name}
                                onChange={(e) => patchAlterIndex(index.id, { name: e.target.value })}
                                placeholder='idx_name'
                                disabled={index.primary || index.dropped}
                              />
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={index.unique || index.primary}
                                  onCheckedChange={(checked) =>
                                    patchAlterIndex(index.id, { unique: Boolean(checked) })
                                  }
                                  disabled={index.primary || index.dropped}
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={index.primary}
                                  onCheckedChange={(checked) =>
                                    patchAlterIndex(index.id, {
                                      primary: Boolean(checked),
                                      unique: Boolean(checked) || index.unique,
                                      name: Boolean(checked) ? 'PRIMARY' : index.name,
                                    })
                                  }
                                  disabled={index.dropped}
                                />
                              </div>
                            </TableCell>
                            <TableCell>
                              <Select
                                value={index.indexType || 'BTREE'}
                                onValueChange={(value) => patchAlterIndex(index.id, { indexType: value })}
                                disabled={index.primary || index.dropped}
                              >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                  <SelectItem value='BTREE'>BTREE</SelectItem>
                                  <SelectItem value='HASH'>HASH</SelectItem>
                                </SelectContent>
                              </Select>
                            </TableCell>
                            <TableCell>
                              <Popover>
                                <PopoverTrigger asChild>
                                  <Button
                                    variant='outline'
                                    className='w-full justify-between'
                                    disabled={index.dropped}
                                  >
                                    <span className='truncate text-left'>
                                      {parseIndexColumns(index.columns).length > 0
                                        ? parseIndexColumns(index.columns).join(', ')
                                        : '请选择索引字段'}
                                    </span>
                                    <ChevronsUpDown className='ml-2 h-4 w-4 shrink-0 opacity-50' />
                                  </Button>
                                </PopoverTrigger>
                                <PopoverContent align='start' className='w-[320px] p-2'>
                                  <div className='max-h-56 space-y-1 overflow-y-auto'>
                                    {availableTableColumns.length === 0 ? (
                                      <div className='px-2 py-1 text-xs text-muted-foreground'>暂无可选字段</div>
                                    ) : (
                                      availableTableColumns.map((name) => {
                                        const checked = parseIndexColumns(index.columns).includes(name)
                                        return (
                                          <label
                                            key={name}
                                            className='flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1 hover:bg-muted'
                                          >
                                            <Checkbox
                                              checked={checked}
                                              onCheckedChange={(value) =>
                                                toggleIndexColumn(index.id, name, Boolean(value))
                                              }
                                            />
                                            <span className='text-sm'>{name}</span>
                                          </label>
                                        )
                                      })
                                    )}
                                  </div>
                                </PopoverContent>
                              </Popover>
                            </TableCell>
                            <TableCell>
                              <Input
                                value={index.comment}
                                onChange={(e) => patchAlterIndex(index.id, { comment: e.target.value })}
                                disabled={index.primary || index.dropped}
                              />
                            </TableCell>
                            <TableCell>
                              <div className='flex justify-center'>
                                <Checkbox
                                  checked={index.dropped}
                                  onCheckedChange={(checked) =>
                                    patchAlterIndex(index.id, { dropped: Boolean(checked) })
                                  }
                                />
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                </div>

                <div className='rounded-md border'>
                  <div className='flex items-center justify-between border-b px-3 py-2'>
                    <div className='text-sm font-medium'>外键约束（支持新增/修改/删除）</div>
                    <Button
                      variant='outline'
                      size='sm'
                      onClick={() =>
                        setAlterForeignKeys((prev) => [
                          ...prev,
                          {
                            ...createForeignKeyDraft({
                              name: '',
                              columnName: '',
                              referenceTable: '',
                              referenceColumn: '',
                              onDelete: 'RESTRICT',
                              onUpdate: 'RESTRICT',
                            }),
                            originalName: '',
                            dirty: true,
                            dropped: false,
                            isNew: true,
                          },
                        ])
                      }
                    >
                      <Plus className='mr-1 h-4 w-4' />新增外键
                    </Button>
                  </div>
                  <div className='w-full max-h-[320px] overflow-y-auto overflow-x-auto'>
                    <Table className='min-w-[1320px] w-max [&_input]:min-w-[140px] [&_[data-slot=select-trigger]]:min-w-[140px]'>
                      <TableHeader className='[&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:bg-background [&_th]:whitespace-nowrap'>
                        <TableRow>
                          <TableHead className='min-w-[160px]'>约束名</TableHead>
                          <TableHead className='min-w-[150px]'>本表字段</TableHead>
                          <TableHead className='min-w-[160px]'>引用表</TableHead>
                          <TableHead className='min-w-[160px]'>引用字段</TableHead>
                          <TableHead className='min-w-[150px]'>删除动作</TableHead>
                          <TableHead className='min-w-[150px]'>更新动作</TableHead>
                          <TableHead className='w-[80px]'>删除</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {alterForeignKeys.length === 0 ? (
                          <TableRow>
                            <TableCell colSpan={7} className='h-14 text-sm text-muted-foreground'>
                              暂无外键，可点击“新增外键”添加。
                            </TableCell>
                          </TableRow>
                        ) : (
                          alterForeignKeys.map((fk) => (
                            <TableRow key={fk.id}>
                              <TableCell>
                                <Input
                                  value={fk.name}
                                  onChange={(e) => patchAlterForeignKey(fk.id, { name: e.target.value })}
                                  placeholder='fk_table_ref'
                                  disabled={fk.dropped}
                                />
                              </TableCell>
                              <TableCell>
                                <Select
                                  value={fk.columnName || '__empty__'}
                                  onValueChange={(value) =>
                                    patchAlterForeignKey(fk.id, {
                                      columnName: value === '__empty__' ? '' : value,
                                    })
                                  }
                                  disabled={fk.dropped}
                                >
                                  <SelectTrigger><SelectValue placeholder='选择本表字段' /></SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value='__empty__'>请选择</SelectItem>
                                    {alterExistingColumns
                                      .filter((col) => !col.dropped && col.name.trim())
                                      .map((col) => (
                                        <SelectItem key={col.id} value={col.name.trim()}>
                                          {col.name.trim()}
                                        </SelectItem>
                                      ))}
                                    {alterAddColumns
                                      .filter((col) => col.name.trim())
                                      .map((col) => (
                                        <SelectItem key={col.id} value={col.name.trim()}>
                                          {col.name.trim()}
                                        </SelectItem>
                                      ))}
                                  </SelectContent>
                                </Select>
                              </TableCell>
                              <TableCell>
                                <Select
                                  value={fk.referenceTable || '__empty__'}
                                  onValueChange={async (value) => {
                                    const table = value === '__empty__' ? '' : value
                                    patchAlterForeignKey(fk.id, {
                                      referenceTable: table,
                                      referenceColumn: '',
                                    })
                                    if (table) await ensureReferenceColumnsLoaded(table)
                                  }}
                                  disabled={fk.dropped}
                                >
                                  <SelectTrigger><SelectValue placeholder='选择引用表' /></SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value='__empty__'>请选择</SelectItem>
                                    {list
                                      .filter((item) => item.tableName !== selectedTable)
                                      .map((item) => (
                                        <SelectItem key={item.tableName} value={item.tableName}>
                                          {item.tableName}
                                        </SelectItem>
                                      ))}
                                  </SelectContent>
                                </Select>
                              </TableCell>
                              <TableCell>
                                <Select
                                  value={fk.referenceColumn || '__empty__'}
                                  onValueChange={(value) =>
                                    patchAlterForeignKey(fk.id, {
                                      referenceColumn: value === '__empty__' ? '' : value,
                                    })
                                  }
                                  disabled={fk.dropped || !fk.referenceTable}
                                >
                                  <SelectTrigger><SelectValue placeholder='选择引用字段' /></SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value='__empty__'>请选择</SelectItem>
                                    {(referenceTableColumns[fk.referenceTable] || []).map((columnName) => (
                                      <SelectItem key={columnName} value={columnName}>
                                        {columnName}
                                      </SelectItem>
                                    ))}
                                  </SelectContent>
                                </Select>
                              </TableCell>
                              <TableCell>
                                <Select
                                  value={fk.onDelete}
                                  onValueChange={(value) => patchAlterForeignKey(fk.id, { onDelete: value })}
                                  disabled={fk.dropped}
                                >
                                  <SelectTrigger><SelectValue /></SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value='RESTRICT'>RESTRICT</SelectItem>
                                    <SelectItem value='CASCADE'>CASCADE</SelectItem>
                                    <SelectItem value='SET NULL'>SET NULL</SelectItem>
                                    <SelectItem value='NO ACTION'>NO ACTION</SelectItem>
                                  </SelectContent>
                                </Select>
                              </TableCell>
                              <TableCell>
                                <Select
                                  value={fk.onUpdate}
                                  onValueChange={(value) => patchAlterForeignKey(fk.id, { onUpdate: value })}
                                  disabled={fk.dropped}
                                >
                                  <SelectTrigger><SelectValue /></SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value='RESTRICT'>RESTRICT</SelectItem>
                                    <SelectItem value='CASCADE'>CASCADE</SelectItem>
                                    <SelectItem value='SET NULL'>SET NULL</SelectItem>
                                    <SelectItem value='NO ACTION'>NO ACTION</SelectItem>
                                  </SelectContent>
                                </Select>
                              </TableCell>
                              <TableCell>
                                <div className='flex justify-center'>
                                  <Checkbox
                                    checked={fk.dropped}
                                    onCheckedChange={(checked) =>
                                      patchAlterForeignKey(fk.id, { dropped: Boolean(checked) })
                                    }
                                  />
                                </div>
                              </TableCell>
                            </TableRow>
                          ))
                        )}
                      </TableBody>
                    </Table>
                  </div>
                </div>

                <Separator />
                <div className='space-y-2'>
                  <div className='flex items-center justify-between'>
                    <Label>SQL 预览</Label>
                    <Button
                      variant='outline'
                      size='sm'
                      onClick={() => {
                        setAlterSqlDraft(alterSqlPreview)
                        setAlterTab('sql')
                        toast.success('已同步预览 SQL 到编辑器')
                      }}
                    >
                      同步到 SQL 编辑器
                    </Button>
                  </div>
                  <SqlEditor
                    value={alterSqlPreview}
                    readOnly
                    height={210}
                    className='overflow-hidden rounded-md border'
                  />
                </div>
              </TabsContent>

              <TabsContent value='sql' className='min-h-0 flex-1 space-y-2 overflow-y-auto pr-1'>
                <Label>ALTER TABLE SQL</Label>
                <SqlEditor
                  value={alterSqlDraft}
                  onChange={setAlterSqlDraft}
                  height={520}
                  className='overflow-hidden rounded-md border'
                />
              </TabsContent>
            </Tabs>
          </div>

          <DialogFooter className='shrink-0 border-t px-6 py-4'>
            <Button variant='outline' onClick={() => setAlterOpen(false)}>取消</Button>
            <Button onClick={() => void handleAlterTable()}>执行修改</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  )
}
